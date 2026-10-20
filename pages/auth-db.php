<?php
declare(strict_types=1);

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['expired'] = 'session';
        auth_redirect('login.html?error=timeout');
    }

    $_SESSION['last_activity'] = time();
}

function auth_database(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_NAME') ?: 'student hub';
    $port = getenv('DB_PORT') ? (int) getenv('DB_PORT') : 3306;

    $connection = new mysqli($host, $user, $password, $database, $port);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function auth_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function auth_require_csrf(?string $submittedToken = null): void
{
    $submittedToken ??= (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

    if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(419);
        exit('Your form session expired. Please reload the page and try again.');
    }
}

function auth_require_admin(): void
{
    auth_require_login();
    if (($_SESSION['student_role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Administrator access is required.');
    }
}

function auth_audit_log(
    mysqli $database,
    string $action,
    string $entityType,
    ?int $entityId = null,
    array $details = []
): void {
    $actorId = isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : null;
    $detailsJson = json_encode($details, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    $statement = $database->prepare(
        'INSERT INTO audit_logs (actor_student_id, action, entity_type, entity_id, details)
         VALUES (?, ?, ?, ?, ?)'
    );
    $statement->bind_param('issis', $actorId, $action, $entityType, $entityId, $detailsJson);
    $statement->execute();
    $statement->close();
}

function auth_bind_parameters(mysqli_stmt $statement, string $types, array &$values): void
{
    if (strlen($types) !== count($values)) {
        throw new InvalidArgumentException('The parameter type count must match the value count.');
    }

    $arguments = [$types];
    foreach ($values as &$value) {
        $arguments[] = &$value;
    }
    unset($value);
    call_user_func_array([$statement, 'bind_param'], $arguments);
}

function auth_redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function auth_post_only(string $formPage): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        auth_redirect($formPage);
    }
}

function auth_refresh_session_user(): ?array
{
    auth_start_session();
    if (empty($_SESSION['student_id'])) {
        return null;
    }

    $database = auth_database();
    try {
        $studentId = (int) $_SESSION['student_id'];
        $statement = $database->prepare(
            "SELECT student_code, full_name, email, role
             FROM students WHERE student_id = ? AND account_status = 'active' LIMIT 1"
        );
        $statement->bind_param('i', $studentId);
        $statement->execute();
        $student = $statement->get_result()->fetch_assoc();
        $statement->close();
    } finally {
        $database->close();
    }

    if (!$student) {
        auth_logout();
        return null;
    }

    $_SESSION['student_code'] = $student['student_code'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_email'] = $student['email'];
    $_SESSION['student_role'] = $student['role'];

    return $student;
}

function auth_require_login(string $redirectPage = 'login.html'): void
{
    auth_start_session();

    if (empty($_SESSION['student_id'])) {
        auth_redirect($redirectPage . '?error=required');
    }

    try {
        $student = auth_refresh_session_user();
    } catch (mysqli_sql_exception $error) {
        error_log('Student Hub session validation error: ' . $error->getMessage());
        http_response_code(503);
        exit('Account verification is temporarily unavailable. Please try again later.');
    }

    if (!$student) {
        auth_redirect($redirectPage . '?error=invalid');
    }
}

function auth_logout(): void
{
    auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
