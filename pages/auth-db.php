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

function auth_require_csrf(): void
{
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

    if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(419);
        exit('Your form session expired. Please reload the page and try again.');
    }
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

function auth_require_login(string $redirectPage = 'login.html'): void
{
    auth_start_session();

    if (empty($_SESSION['student_id'])) {
        auth_redirect($redirectPage . '?error=required');
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
