<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$respond = static function (int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

try {
    $currentUser = auth_refresh_session_user();
} catch (mysqli_sql_exception $error) {
    error_log('Student Hub API session validation error: ' . $error->getMessage());
    $respond(503, ['error' => 'authentication_unavailable']);
}
if (!$currentUser) {
    $respond(401, ['error' => 'authentication_required']);
}
if ($currentUser['role'] !== 'admin') {
    $respond(403, ['error' => 'administrator_required']);
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, PATCH, DELETE');
    $respond(405, ['error' => 'method_not_allowed']);
}
if ($method !== 'GET') {
    $submittedToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        $respond(419, ['error' => 'csrf_validation_failed']);
    }
}
if (($_GET['resource'] ?? '') !== 'events') {
    $respond(404, ['error' => 'resource_not_found']);
}

$input = $_POST;
if (in_array($method, ['PUT', 'PATCH'], true)) {
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        $respond(400, ['error' => 'invalid_json']);
    }
    $input = $decoded;
}

$database = null;
$newPoster = null;
$transactionOpen = false;
try {
    $database = auth_database();

    if ($method === 'GET') {
        $events = $database->query(
            "SELECT e.event_id, e.title, e.description, e.category, e.starts_at, e.location, e.capacity,
                    e.status, e.poster_path,
                    COUNT(CASE WHEN r.registration_status = 'registered' THEN 1 END) AS registration_count
             FROM events e
             LEFT JOIN event_registrations r ON r.event_id = e.event_id
             GROUP BY e.event_id
             ORDER BY e.starts_at"
        )->fetch_all(MYSQLI_ASSOC);
        $respond(200, ['data' => $events]);
    }

    if ($method === 'DELETE') {
        $eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$eventId || $eventId < 1) {
            $respond(422, ['error' => 'valid_event_id_required']);
        }

        $find = $database->prepare('SELECT poster_path FROM events WHERE event_id = ?');
        $find->bind_param('i', $eventId);
        $find->execute();
        $event = $find->get_result()->fetch_assoc();
        $find->close();
        if (!$event) {
            $respond(404, ['error' => 'event_not_found']);
        }

        $database->begin_transaction();
        $transactionOpen = true;
        $delete = $database->prepare('DELETE FROM events WHERE event_id = ?');
        $delete->bind_param('i', $eventId);
        $delete->execute();
        $delete->close();
        auth_audit_log($database, 'event.deleted', 'event', $eventId);
        $database->commit();
        $transactionOpen = false;
        if ($event['poster_path']) {
            $posterFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $event['poster_path']);
            if (is_file($posterFile) && !unlink($posterFile)) {
                error_log('Student Hub could not remove deleted event poster: ' . $posterFile);
            }
        }
        $respond(200, ['message' => 'Event deleted.']);
    }

    $eventId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if ($method !== 'POST' && (!$eventId || $eventId < 1)) {
        $respond(422, ['error' => 'valid_event_id_required']);
    }

    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));
    $location = trim((string) ($input['location'] ?? ''));
    $startsAtInput = trim((string) ($input['starts_at'] ?? ''));
    $capacityInput = trim((string) ($input['capacity'] ?? ''));
    $eventStatus = trim((string) ($input['status'] ?? 'open'));
    $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $startsAtInput);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $title === '' || strlen($title) > 180
        || strlen($description) > 10000
        || $category === '' || strlen($category) > 80
        || $location === '' || strlen($location) > 180
        || !$startsAt || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $startsAt->format('Y-m-d\TH:i') !== $startsAtInput
        || !in_array($eventStatus, ['open', 'closed'], true)
        || ($capacityInput !== '' && (!ctype_digit($capacityInput) || (int) $capacityInput < 1 || (int) $capacityInput > 100000))
    ) {
        $respond(422, ['error' => 'validation_failed', 'message' => 'Check the event fields and try again.']);
    }
    $capacity = $capacityInput === '' ? null : (int) $capacityInput;
    $startsAtSql = $startsAt->format('Y-m-d H:i:s');

    $existingPoster = null;
    if ($eventId) {
        $find = $database->prepare('SELECT poster_path FROM events WHERE event_id = ?');
        $find->bind_param('i', $eventId);
        $find->execute();
        $existing = $find->get_result()->fetch_assoc();
        $find->close();
        if (!$existing) {
            $respond(404, ['error' => 'event_not_found']);
        }
        $existingPoster = $existing['poster_path'];
    }

    if (isset($_FILES['poster']) && (int) $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['poster'];
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $respond(422, ['error' => 'poster_upload_failed']);
        }
        if ((int) $upload['size'] < 1 || (int) $upload['size'] > 2 * 1024 * 1024) {
            $respond(422, ['error' => 'poster_too_large']);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $imageInfo = @getimagesize((string) $upload['tmp_name']);
        if (!isset($extensions[$mime]) || $imageInfo === false) {
            $respond(422, ['error' => 'poster_type_not_allowed']);
        }

        $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('The event poster directory could not be created.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file((string) $upload['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $fileName)) {
            throw new RuntimeException('The event poster could not be stored.');
        }
        $newPoster = 'uploads/events/' . $fileName;
    }

    $posterPath = $newPoster ?? $existingPoster;
    $database->begin_transaction();
    $transactionOpen = true;
    if ($eventId) {
        $statement = $database->prepare(
            'UPDATE events
             SET title = ?, description = ?, category = ?, starts_at = ?, location = ?, capacity = ?, status = ?, poster_path = ?
             WHERE event_id = ?'
        );
        $statement->bind_param('sssssissi', $title, $description, $category, $startsAtSql, $location, $capacity, $eventStatus, $posterPath, $eventId);
        $statement->execute();
        $statement->close();
        auth_audit_log($database, 'event.updated', 'event', $eventId, ['title' => $title]);
        $savedId = $eventId;
    } else {
        $statement = $database->prepare(
            'INSERT INTO events (title, description, category, starts_at, location, capacity, status, poster_path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('sssssiss', $title, $description, $category, $startsAtSql, $location, $capacity, $eventStatus, $posterPath);
        $statement->execute();
        $savedId = (int) $database->insert_id;
        $statement->close();
        auth_audit_log($database, 'event.created', 'event', $savedId, ['title' => $title]);
    }
    $database->commit();
    $transactionOpen = false;

    if ($newPoster !== null && $existingPoster !== null) {
        $oldPosterFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $existingPoster);
        if (is_file($oldPosterFile) && !unlink($oldPosterFile)) {
            error_log('Student Hub could not remove replaced event poster: ' . $oldPosterFile);
        }
    }
    $respond($eventId ? 200 : 201, ['message' => 'Event saved.', 'id' => $savedId]);
} catch (mysqli_sql_exception $error) {
    if ($transactionOpen && $database instanceof mysqli) {
        $database->rollback();
    }
    if ($newPoster !== null) {
        $newPosterFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newPoster);
        if (is_file($newPosterFile)) {
            unlink($newPosterFile);
        }
    }
    error_log('Student Hub events API database error: ' . $error->getMessage());
    $respond(500, ['error' => 'database_error']);
} catch (Throwable $error) {
    if ($transactionOpen && $database instanceof mysqli) {
        $database->rollback();
    }
    if ($newPoster !== null) {
        $newPosterFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newPoster);
        if (is_file($newPosterFile)) {
            unlink($newPosterFile);
        }
    }
    error_log('Student Hub events API error: ' . $error->getMessage());
    $respond(500, ['error' => 'server_error']);
} finally {
    if ($database instanceof mysqli) {
        $database->close();
    }
}
