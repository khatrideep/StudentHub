<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
auth_post_only('home.html');
auth_require_csrf();

$action = (string) ($_GET['action'] ?? '');
$database = null;

try {
    $database = auth_database();

    if ($action === 'feedback') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $category = trim((string) ($_POST['category'] ?? 'General'));
        $rating = (string) ($_POST['rating'] ?? '');
        $message = trim((string) ($_POST['message'] ?? ''));

        if (
            !preg_match("/^[\\p{L}][\\p{L}\\s'-]{1,99}$/u", $name)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 254
            || !in_array($category, ['Campus facility', 'Academic', 'Event'], true)
            || !in_array($rating, ['1', '2', '3', '4', '5'], true)
            || $message === ''
            || strlen($message) > 10000
        ) {
            auth_redirect('../pages/feedback.html?error=invalid');
        }

        $studentId = isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : null;
        $statement = $database->prepare(
            'INSERT INTO feedback (student_id, subject, category, rating, submitter_name, submitter_email, message)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $subject = $category . ' feedback';
        $statement->bind_param('ississs', $studentId, $subject, $category, $rating, $name, $email, $message);
        $statement->execute();
        $statement->close();
        auth_redirect('../pages/feedback.html?sent=1');
    }

    if ($action === 'contact') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $message = trim((string) ($_POST['message'] ?? ''));

        if (
            !preg_match("/^[\\p{L}][\\p{L}\\s'-]{1,99}$/u", $name)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 254
            || $message === ''
            || strlen($message) > 10000
        ) {
            auth_redirect('../pages/contact.html?error=invalid');
        }

        $statement = $database->prepare('INSERT INTO contact_messages (full_name, email, message) VALUES (?, ?, ?)');
        $statement->bind_param('sss', $name, $email, $message);
        $statement->execute();
        $statement->close();
        auth_redirect('../pages/contact.html?sent=1');
    }

    if ($action === 'profile') {
        auth_require_login();
        $name = trim((string) ($_POST['fullName'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $program = trim((string) ($_POST['program'] ?? ''));
        $year = trim((string) ($_POST['year'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));

        if (
            !preg_match("/^[\\p{L}][\\p{L}\\s'-]{1,99}$/u", $name)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 254
            || ($phone !== '' && !preg_match('/^\\+?[0-9]{10,30}$/', $phone))
            || strlen($program) > 120
            || strlen($address) > 255
            || strlen($bio) > 500
        ) {
            auth_redirect('dashboard.php?error=profile');
        }

        $studentId = (int) $_SESSION['student_id'];
        $statement = $database->prepare(
            'UPDATE students SET full_name = ?, email = ?, phone = ?, program = ?, academic_year = ?, address = ?, bio = ? WHERE student_id = ?'
        );
        $statement->bind_param('sssssssi', $name, $email, $phone, $program, $year, $address, $bio, $studentId);
        $statement->execute();
        $statement->close();
        $_SESSION['student_name'] = $name;
        $_SESSION['student_email'] = $email;
        auth_redirect('dashboard.php?profile=updated');
    }

    if ($action === 'event-register') {
        auth_require_login();
        $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        if (!$eventId || $eventId < 1) {
            auth_redirect('../pages/events.php?error=invalid');
        }

        $studentId = (int) $_SESSION['student_id'];
        $database->begin_transaction();
        $event = $database->prepare(
            'SELECT capacity, (SELECT COUNT(*) FROM event_registrations WHERE event_id = events.event_id AND registration_status = "registered") AS registrations
             FROM events WHERE event_id = ? AND starts_at >= NOW() FOR UPDATE'
        );
        $event->bind_param('i', $eventId);
        $event->execute();
        $eventRow = $event->get_result()->fetch_assoc();
        $event->close();

        if (!$eventRow) {
            $database->rollback();
            auth_redirect('../pages/events.php?error=unavailable');
        }

        $check = $database->prepare(
            'SELECT registration_status FROM event_registrations WHERE event_id = ? AND student_id = ?'
        );
        $check->bind_param('ii', $eventId, $studentId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing && $existing['registration_status'] === 'registered') {
            $database->rollback();
            auth_redirect('../pages/events.php?registered=already');
        }
        if ($eventRow['capacity'] !== null && (int) $eventRow['registrations'] >= (int) $eventRow['capacity']) {
            $database->rollback();
            auth_redirect('../pages/events.php?error=full');
        }

        $register = $database->prepare(
            "INSERT INTO event_registrations (event_id, student_id, registration_status)
             VALUES (?, ?, 'registered')
             ON DUPLICATE KEY UPDATE registration_status = 'registered', registered_at = CURRENT_TIMESTAMP"
        );
        $register->bind_param('ii', $eventId, $studentId);
        $register->execute();
        $register->close();
        $database->commit();
        auth_redirect('../pages/events.php?registered=1');
    }

    if ($action === 'notice') {
        auth_require_login();
        if (($_SESSION['student_role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Administrator access is required.');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($title === '' || strlen($title) > 180 || $message === '' || strlen($message) > 10000) {
            auth_redirect('admin.php?error=notice');
        }

        $statement = $database->prepare('INSERT INTO notices (title, body) VALUES (?, ?)');
        $statement->bind_param('ss', $title, $message);
        $statement->execute();
        $statement->close();
        auth_redirect('admin.php?notice=published');
    }

    if ($action === 'message-status') {
        auth_require_login();
        if (($_SESSION['student_role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Administrator access is required.');
        }

        $recordType = (string) ($_POST['record_type'] ?? '');
        $recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');
        $tables = [
            'feedback' => ['feedback', 'feedback_id'],
            'contact' => ['contact_messages', 'contact_message_id'],
        ];
        if (!isset($tables[$recordType]) || !$recordId || !in_array($status, ['new', 'reviewed', 'resolved'], true)) {
            auth_redirect('admin.php?error=status');
        }

        [$table, $idColumn] = $tables[$recordType];
        $statement = $database->prepare("UPDATE {$table} SET status = ? WHERE {$idColumn} = ?");
        $statement->bind_param('si', $status, $recordId);
        $statement->execute();
        $statement->close();
        auth_redirect('admin.php?status=updated');
    }

    http_response_code(400);
    exit('Unknown action.');
} catch (mysqli_sql_exception $exception) {
    if ($database instanceof mysqli) {
        $database->rollback();
    }
    error_log('Student Hub action database error: ' . $exception->getMessage());
    $target = match ($action) {
        'feedback' => '../pages/feedback.html?error=database',
        'contact' => '../pages/contact.html?error=database',
        'profile' => 'dashboard.php?error=database',
        'event-register' => '../pages/events.php?error=database',
        'notice' => 'admin.php?error=database',
        'message-status' => 'admin.php?error=database',
        default => '../index.html',
    };
    auth_redirect($target);
} finally {
    if ($database instanceof mysqli) {
        $database->close();
    }
}
