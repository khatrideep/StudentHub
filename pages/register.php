<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';
require_once __DIR__ . '/registration-export.php';

auth_start_session();
auth_post_only('register.html');
auth_require_csrf();

$postString = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
};

$fullName = trim($postString('fullName'));
$mobile = trim($postString('mobile'));
$email = strtolower(trim($postString('email')));
$dateOfBirth = trim($postString('dob'));
$course = trim($postString('course'));
$year = trim($postString('year'));
$gender = trim($postString('gender'));
$password = $postString('password');
$confirmPassword = $postString('confirmPassword');

$validCourses = ['BCA', 'BSc Computer Science', 'BBA', 'BCom', 'BA'];
$validYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$validGenders = ['Male', 'Female', 'Other'];
$validationErrors = [];

if (!preg_match("/^[\\p{L}][\\p{L}\\s'-]{1,99}$/uD", $fullName)) {
    $validationErrors['fullName'] = true;
}
if (!preg_match('/^\\+?[0-9]{10,15}$/D', $mobile)) {
    $validationErrors['mobile'] = true;
}
if (
    strlen($email) > 254
    || !preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$/D', $email)
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $validationErrors['email'] = true;
}
if (!in_array($course, $validCourses, true)) {
    $validationErrors['course'] = true;
}
if (!in_array($year, $validYears, true)) {
    $validationErrors['year'] = true;
}
if (!in_array($gender, $validGenders, true)) {
    $validationErrors['gender'] = true;
}
if (($_POST['terms'] ?? '') !== 'on') {
    $validationErrors['terms'] = true;
}

$birthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateOfBirth);
$dateErrors = DateTimeImmutable::getLastErrors();
if (
    !$birthDate
    || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
    || $birthDate->format('Y-m-d') !== $dateOfBirth
    || $birthDate > new DateTimeImmutable('today')
) {
    $validationErrors['dob'] = true;
}

if (
    strlen($password) < 8
    || strlen($password) > 16
    || !preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9@#$%^&*!_~]{8,16}$/', $password)
) {
    $validationErrors['password'] = true;
}

if ($confirmPassword === '' || !hash_equals($password, $confirmPassword)) {
    $validationErrors['confirmPassword'] = true;
}

if ($validationErrors) {
    $invalidFields = implode(',', array_keys($validationErrors));
    auth_redirect('register.html?error=invalid&fields=' . rawurlencode($invalidFields));
}

try {
    $database = auth_database();
    $database->begin_transaction();

    $emailCheck = $database->prepare('SELECT student_id FROM students WHERE email = ? LIMIT 1');
    $emailCheck->bind_param('s', $email);
    $emailCheck->execute();
    $emailCheck->store_result();

    if ($emailCheck->num_rows > 0) {
        $emailCheck->close();
        $database->rollback();
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
    $registration = [
        'student_code' => $studentCode,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $mobile,
        'date_of_birth' => $dateOfBirth,
        'gender' => $gender,
        'program' => $course,
        'academic_year' => $year,
        'registered_at' => (new DateTimeImmutable())->format(DATE_ATOM),
    ];
    $insert->close();
    append_student_registration_exports($registration);
    $database->commit();
    $database->close();

    auth_redirect('login.html?registered=1');
} catch (mysqli_sql_exception $exception) {
    if (isset($database) && $database instanceof mysqli) {
        $database->rollback();
        $database->close();
    }
    error_log('Student Hub registration database error: ' . $exception->getMessage());
    auth_redirect($exception->getCode() === 1062 ? 'register.html?error=email_taken' : 'register.html?error=database');
} catch (RegistrationExportException $exception) {
    if (isset($database) && $database instanceof mysqli) {
        $database->rollback();
        $database->close();
    }
    error_log('Student Hub registration export error: ' . $exception->getMessage());
    auth_redirect('register.html?error=export');
} catch (Throwable $exception) {
    if (isset($database) && $database instanceof mysqli) {
        $database->rollback();
        $database->close();
    }
    error_log('Student Hub registration error: ' . $exception->getMessage());
    auth_redirect('register.html?error=database');
}
