<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_require_login();
$database = auth_database();
$studentId = (int) $_SESSION['student_id'];
$profileStatement = $database->prepare(
    'SELECT student_id, student_code, full_name, email, phone, program, academic_year, address, bio
     FROM students WHERE student_id = ? AND account_status = "active" LIMIT 1'
);
$profileStatement->bind_param('i', $studentId);
$profileStatement->execute();
$student = $profileStatement->get_result()->fetch_assoc();
$profileStatement->close();
if (!$student) {
    $database->close();
    auth_logout();
    auth_redirect('login.html?error=invalid');
}

$attendanceStatement = $database->prepare(
    "SELECT COUNT(*) AS total, COALESCE(SUM(attendance_status = 'present'), 0) AS present
     FROM attendance_records WHERE student_id = ?"
);
$attendanceStatement->bind_param('i', $studentId);
$attendanceStatement->execute();
$attendance = $attendanceStatement->get_result()->fetch_assoc();
$attendanceStatement->close();

$assignmentStatement = $database->prepare(
    "SELECT a.assignment_id, a.title, a.subject, a.description, a.due_at,
            COALESCE(s.status, IF(a.due_at < NOW(), 'overdue', 'pending')) AS submission_status
     FROM assignments a
     LEFT JOIN assignment_submissions s ON s.assignment_id = a.assignment_id AND s.student_id = ?
     ORDER BY a.due_at IS NULL, a.due_at LIMIT 20"
);
$assignmentStatement->bind_param('i', $studentId);
$assignmentStatement->execute();
$assignments = $assignmentStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$assignmentStatement->close();

$noticeQuery = $database->query(
    'SELECT title, body, published_at FROM notices WHERE expires_at IS NULL OR expires_at >= NOW()
     ORDER BY published_at DESC LIMIT 10'
);
$notices = $noticeQuery->fetch_all(MYSQLI_ASSOC);
$eventStatement = $database->prepare(
    "SELECT e.title, e.starts_at, e.location, r.registration_status
     FROM event_registrations r JOIN events e ON e.event_id = r.event_id
     WHERE r.student_id = ? AND r.registration_status = 'registered' AND e.starts_at >= NOW()
     ORDER BY e.starts_at LIMIT 10"
);
$eventStatement->bind_param('i', $studentId);
$eventStatement->execute();
$registeredEvents = $eventStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$eventStatement->close();
$database->close();

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$attendanceTotal = (int) ($attendance['total'] ?? 0);
$attendancePercent = $attendanceTotal > 0 ? (int) round((int) $attendance['present'] * 100 / $attendanceTotal) : 0;
$pendingAssignments = count(array_filter($assignments, static fn (array $assignment): bool => in_array($assignment['submission_status'], ['pending', 'overdue'], true)));
$csrfToken = auth_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Dashboard</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/site.css">
</head>
<body class="site-page">
    <div class="page-wrapper">
        <main class="main-content">
            <header class="navbar">
                <a href="../index.html" class="brand"><div class="logo"><img src="../logo.jpeg" alt="Student Hub Logo"></div>Student Hub</a>
                <button class="theme-toggle" type="button" aria-label="Switch to dark theme" aria-pressed="false">Dark mode</button>
                <ul class="nav-links">
                    <li><a href="../index.html">Home</a></li><li><a href="about.html">About</a></li>
                    <li><a href="events.php">Events</a></li><li><a href="notices.php">Notices</a></li>
                    <li><a href="students.html">Students</a></li><li><a href="#profile" aria-current="page">Profile</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if (($_SESSION['student_role'] ?? '') === 'admin'): ?><li><a href="admin.php">Admin</a></li><?php endif; ?>
                    <li><a href="feedback.html">Feedback</a></li><li><a href="faqs.php">FAQs</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </header>
            <h1>Student Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo $escape($student['full_name']); ?>.</p>
            <?php if (isset($_GET['profile'])): ?><p class="auth-message" role="status">Your profile was saved.</p><?php endif; ?>
            <?php if (isset($_GET['error'])): ?><p class="auth-message" role="alert">Your profile could not be saved. Check the details and try again.</p><?php endif; ?>
            <div class="grid-4" style="margin:20px 0;">
                <div class="card"><small>Attendance</small><strong style="font-size:1.8rem;"><?php echo $attendancePercent; ?>%</strong></div>
                <div class="card"><small>Assignments</small><strong style="font-size:1.8rem;"><?php echo count($assignments); ?></strong></div>
                <div class="card"><small>To complete</small><strong style="font-size:1.8rem;"><?php echo $pendingAssignments; ?></strong></div>
                <div class="card"><small>Registered events</small><strong style="font-size:1.8rem;"><?php echo count($registeredEvents); ?></strong></div>
            </div>
            <div class="grid-2">
                <section class="panel" id="profile">
                    <h2>My profile</h2>
                    <p><strong>Student ID:</strong> <?php echo $escape($student['student_code']); ?></p>
                    <p><strong>Email:</strong> <?php echo $escape($student['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo $escape($student['phone'] ?? 'Not provided'); ?></p>
                    <p><strong>Program:</strong> <?php echo $escape($student['program'] ?? 'Not provided'); ?></p>
                    <p><strong>Year:</strong> <?php echo $escape($student['academic_year'] ?? 'Not provided'); ?></p>
                    <button class="btn" id="editProfileButton" type="button" aria-expanded="false">Edit profile</button>
                    <form class="panel profile-edit-form" id="profileEditForm" action="actions.php?action=profile" method="post" hidden>
                        <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                        <div class="form-group"><label for="profileFullName">Full name</label><input id="profileFullName" name="fullName" value="<?php echo $escape($student['full_name']); ?>" maxlength="100" required></div>
                        <div class="form-group"><label for="profileEmail">Email</label><input id="profileEmail" name="email" type="email" value="<?php echo $escape($student['email']); ?>" maxlength="254" required></div>
                        <div class="form-group"><label for="profilePhone">Phone</label><input id="profilePhone" name="phone" value="<?php echo $escape($student['phone'] ?? ''); ?>" maxlength="30"></div>
                        <div class="form-group"><label for="profileProgram">Program</label><input id="profileProgram" name="program" value="<?php echo $escape($student['program'] ?? ''); ?>" maxlength="120"></div>
                        <div class="form-group"><label for="profileYear">Academic year</label><select id="profileYear" name="year">
                            <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year', 'Staff'] as $year): ?>
                                <option value="<?php echo $escape($year); ?>" <?php echo ($student['academic_year'] ?? '') === $year ? 'selected' : ''; ?>><?php echo $escape($year); ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div class="form-group"><label for="profileAddress">Address</label><textarea id="profileAddress" name="address" maxlength="255"><?php echo $escape($student['address'] ?? ''); ?></textarea></div>
                        <div class="form-group"><label for="profileBio">About me</label><textarea id="profileBio" name="bio" maxlength="500"><?php echo $escape($student['bio'] ?? ''); ?></textarea></div>
                        <button type="submit" class="btn">Save profile</button>
                        <button type="button" class="btn btn-outline" id="cancelProfileEdit">Cancel</button>
                    </form>
                </section>
                <section class="panel">
                    <h2>Notices</h2>
                    <?php foreach ($notices as $notice): ?>
                        <article><h3><?php echo $escape($notice['title']); ?></h3><p><?php echo nl2br($escape($notice['body'])); ?></p><small><?php echo $escape($notice['published_at']); ?></small></article>
                    <?php endforeach; ?>
                    <?php if (!$notices): ?><p>There are no current notices.</p><?php endif; ?>
                </section>
            </div>
            <section class="panel">
                <h2>My assignments</h2>
                <?php if ($assignments): ?>
                    <div class="table-responsive"><table><thead><tr><th>Assignment</th><th>Subject</th><th>Due</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr><td><?php echo $escape($assignment['title']); ?></td><td><?php echo $escape($assignment['subject']); ?></td><td><?php echo $escape($assignment['due_at'] ?? 'Not scheduled'); ?></td><td><?php echo $escape(ucfirst($assignment['submission_status'])); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?><p>No assignments have been published yet.</p><?php endif; ?>
            </section>
            <section class="panel">
                <h2>My attendance</h2>
                <?php if ($attendanceTotal > 0): ?>
                    <p><?php echo (int) $attendance['present']; ?> of <?php echo $attendanceTotal; ?> classes marked present.</p>
                <?php else: ?><p>Attendance has not been recorded yet.</p><?php endif; ?>
            </section>
            <section class="panel">
                <h2>My upcoming events</h2>
                <?php foreach ($registeredEvents as $event): ?><p><?php echo $escape($event['title']); ?> · <?php echo $escape($event['starts_at']); ?></p><?php endforeach; ?>
                <?php if (!$registeredEvents): ?><p>You have not registered for an upcoming event. <a href="events.php">Browse events</a>.</p><?php endif; ?>
            </section>
        </main>
        <footer class="footer"><p>&copy; 2026 Student Hub &middot; <a href="contact.html">Contact Us</a></p></footer>
    </div>
    <script>
        const profileForm = document.getElementById('profileEditForm');
        const editProfileButton = document.getElementById('editProfileButton');
        document.getElementById('cancelProfileEdit').addEventListener('click', () => {
            profileForm.hidden = true;
            editProfileButton.hidden = false;
            editProfileButton.setAttribute('aria-expanded', 'false');
        });
        editProfileButton.addEventListener('click', () => {
            profileForm.hidden = false;
            editProfileButton.hidden = true;
            editProfileButton.setAttribute('aria-expanded', 'true');
            profileForm.querySelector('input[name="fullName"]').focus();
        });
    </script>
    <script src="../js/theme.js"></script>
</body>
</html>
