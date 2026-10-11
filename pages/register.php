<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
auth_post_only('register.html');
auth_require_csrf();

$fullName = trim((string) ($_POST['fullName'] ?? ''));
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$dateOfBirth = trim((string) ($_POST['dob'] ?? ''));
$course = trim((string) ($_POST['course'] ?? ''));
$year = trim((string) ($_POST['year'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

$validCourses = ['BCA', 'BSc Computer Science', 'BBA', 'BCom', 'BA'];
$validYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$validGenders = ['Male', 'Female', 'Other'];

if (
    !preg_match("/^[\\p{L}][\\p{L}\\s'-]{1,99}$/u", $fullName)
    || !preg_match('/^\\+?[0-9]{10,15}$/', $mobile)
    || strlen($email) > 254
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !in_array($course, $validCourses, true)
    || !in_array($year, $validYears, true)
    || !in_array($gender, $validGenders, true)
    || !isset($_POST['terms'])
) {
    auth_redirect('register.html?error=invalid');
}

$birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateOfBirth);
$dateErrors = DateTimeImmutable::getLastErrors();
if (
    !$birthDate
    || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
    || $birthDate->format('Y-m-d') !== $dateOfBirth
    || $birthDate > new DateTimeImmutable('today')
) {
    auth_redirect('register.html?error=invalid');
}

if (
    strlen($password) < 8
    || strlen($password) > 16
    || !preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9@#$%^&*!_~]{8,16}$/', $password)
) {
    auth_redirect('register.html?error=invalid');
}

if (!hash_equals($password, $confirmPassword)) {
    auth_redirect('register.html?error=password_mismatch');
}

try {
    $database = auth_database();

    $emailCheck = $database->prepare('SELECT student_id FROM students WHERE email = ? LIMIT 1');
    $emailCheck->bind_param('s', $email);
    $emailCheck->execute();
    $emailCheck->store_result();

    if ($emailCheck->num_rows > 0) {
        $emailCheck->close();
        $database->close();
        auth_redirect('register.html?error=email_taken');
    }
    $emailCheck->close();

    $studentCode = 'SH' . strtoupper(bin2hex(random_bytes(4)));
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $database->prepare(
        'INSERT INTO students
            (student_code, full_name, email, password_hash, phone, date_of_birth, gender, program, academic_year, enrollment_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)'
    );
    $insert->bind_param(
        'sssssssss',
        $studentCode,
        $fullName,
        $email,
        $passwordHash,
        $mobile,
        $dateOfBirth,
        $gender,
        $course,
        $year
    );
    $insert->execute();
    $insert->close();
    $database->close();

    auth_redirect('login.html?registered=1');
} catch (mysqli_sql_exception $exception) {
    error_log('Student Hub registration database error: ' . $exception->getMessage());
    auth_redirect($exception->getCode() === 1062 ? 'register.html?error=email_taken' : 'register.html?error=database');
} catch (Throwable $exception) {
    error_log('Student Hub registration error: ' . $exception->getMessage());
    auth_redirect('register.html?error=database');
}
