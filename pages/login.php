<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
auth_post_only('login.html');
auth_require_csrf();

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '' || strlen($password) > 72) {
    auth_redirect('login.html?error=required');
}

try {
    $database = auth_database();
    $statement = $database->prepare(
        "SELECT student_id, student_code, full_name, email, password_hash, role
         FROM students
         WHERE email = ? AND account_status = 'active'
         LIMIT 1"
    );
    $statement->bind_param('s', $email);
    $statement->execute();
    $student = $statement->get_result()->fetch_assoc();
    $statement->close();
    $database->close();

    if (!$student || !password_verify($password, $student['password_hash'])) {
        auth_redirect('login.html?error=invalid');
    }

    session_regenerate_id(true);
    $_SESSION['student_id'] = (int) $student['student_id'];
    $_SESSION['student_code'] = $student['student_code'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_email'] = $student['email'];
    $_SESSION['student_role'] = $student['role'] ?? 'student';
    $_SESSION['last_activity'] = time();

    $redirectPage = ($_SESSION['student_role'] === 'admin') ? 'admin.php' : 'dashboard.php';
    auth_redirect($redirectPage);
} catch (mysqli_sql_exception $exception) {
    error_log('Student Hub login database error: ' . $exception->getMessage());
    auth_redirect('login.html?error=database');
} catch (Throwable $exception) {
    error_log('Student Hub login error: ' . $exception->getMessage());
    auth_redirect('login.html?error=database');
}
