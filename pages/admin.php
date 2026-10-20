<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_require_admin();
$database = auth_database();
$stats = $database->query(
    "SELECT
        (SELECT COUNT(*) FROM students WHERE role = 'student' AND account_status = 'active') AS active_students,
        (SELECT COUNT(*) FROM events WHERE starts_at >= NOW() AND status = 'open') AS upcoming_events,
        (SELECT COUNT(*) FROM event_registrations r JOIN events e ON e.event_id = r.event_id
            WHERE r.registration_status = 'registered' AND e.starts_at >= NOW()) AS upcoming_registrations,
        (SELECT COUNT(*) FROM feedback WHERE status = 'new') +
        (SELECT COUNT(*) FROM contact_messages WHERE status = 'new') AS new_messages"
)->fetch_assoc();
$registrations = $database->query(
    "SELECT r.registered_at, s.full_name, s.email, e.title AS event_title
     FROM event_registrations r
     JOIN students s ON s.student_id = r.student_id
     JOIN events e ON e.event_id = r.event_id
     WHERE r.registration_status = 'registered'
     ORDER BY r.registered_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);
$auditEntries = $database->query(
    'SELECT a.action, a.entity_type, a.entity_id, a.details, a.created_at, s.full_name AS actor_name
     FROM audit_logs a
     LEFT JOIN students s ON s.student_id = a.actor_student_id
     ORDER BY a.created_at DESC LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);
$recentFeedback = $database->query(
    'SELECT feedback_id, category, submitter_name, submitter_email, message, status, created_at
     FROM feedback ORDER BY created_at DESC LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);
$recentContacts = $database->query(
    'SELECT contact_message_id, full_name, email, message, status, created_at
     FROM contact_messages ORDER BY created_at DESC LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);
$notices = $database->query(
    'SELECT notice_id, title, body, published_at
     FROM notices ORDER BY published_at DESC LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);
$csrfToken = auth_csrf_token();
$database->close();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Hub administrator overview, activity, and audit history.">
    <title>Student Hub Admin - Overview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../styles/adminlte-overrides.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary admin-app">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <button class="nav-link" type="button" data-lte-toggle="sidebar" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
            <span class="navbar-text fw-semibold">Student Hub Administration</span>
            <span class="navbar-text ms-auto me-3 d-none d-sm-inline"><?php echo $escape((string) ($_SESSION['student_name'] ?? 'Administrator')); ?></span>
            <a class="btn btn-dark btn-sm" href="logout.php">Sign out</a>
        </div>
    </nav>
    <aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
        <div class="sidebar-brand"><a href="admin.php" class="brand-link"><span class="brand-text fw-light">StudentHub</span></a></div>
        <div class="sidebar-wrapper">
            <nav aria-label="Administration">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                    <li class="nav-item"><a href="admin.php" class="nav-link active" aria-current="page"><i class="nav-icon bi bi-speedometer2"></i><p>Overview</p></a></li>
                    <li class="nav-item"><a href="admin-students.php" class="nav-link"><i class="nav-icon bi bi-people"></i><p>Students</p></a></li>
                    <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="nav-icon bi bi-calendar-event"></i><p>Events</p></a></li>
                    <li class="nav-item"><a href="notices.php" class="nav-link"><i class="nav-icon bi bi-megaphone"></i><p>Public notices</p></a></li>
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon bi bi-house"></i><p>Student portal</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <h1 class="mb-1">Administration overview</h1>
                <p class="text-secondary mb-0">Review student activity, manage campus services, and monitor administrator actions.</p>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if (isset($_GET['notice'])): ?><div class="alert alert-success" role="status">The notice was published.</div><?php endif; ?>
                <?php if (isset($_GET['status'])): ?><div class="alert alert-success" role="status">The message status was updated.</div><?php endif; ?>
                <?php if (isset($_GET['error'])): ?><div class="alert alert-danger" role="alert">We could not complete that action. Please check the form and try again.</div><?php endif; ?>
                <div class="row">
                    <?php foreach ([
                        ['Active students', $stats['active_students'], 'bi-people'],
                        ['Upcoming events', $stats['upcoming_events'], 'bi-calendar-event'],
                        ['Upcoming RSVPs', $stats['upcoming_registrations'], 'bi-ticket-perforated'],
                        ['New messages', $stats['new_messages'], 'bi-chat-left-text'],
                    ] as [$label, $count, $icon]): ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <section class="small-box admin-stat">
                            <div class="inner"><p><?php echo $escape((string) $label); ?></p><h3><?php echo (int) $count; ?></h3></div>
                            <i class="small-box-icon bi <?php echo $escape((string) $icon); ?>" aria-hidden="true"></i>
                        </section>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-xl-7">
                        <section class="card shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h2 class="card-title h5 mb-0">Recent event registrations</h2>
                                <a href="admin-events.php" class="btn btn-outline-secondary btn-sm">Manage events</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th scope="col">Student</th><th scope="col">Event</th><th scope="col">Registered</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($registrations as $registration): ?>
                                        <tr><td><strong><?php echo $escape($registration['full_name']); ?></strong><br><small><?php echo $escape($registration['email']); ?></small></td><td><?php echo $escape($registration['event_title']); ?></td><td><?php echo $escape($registration['registered_at']); ?></td></tr>
                                    <?php endforeach; ?>
                                    <?php if (!$registrations): ?><tr><td colspan="3" class="text-center text-secondary py-4">No event registrations yet.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                    <div class="col-12 col-xl-5">
                        <section class="card shadow-sm">
                            <div class="card-header"><h2 class="card-title h5 mb-0">Publish a notice</h2></div>
                            <div class="card-body">
                                <form action="actions.php?action=notice" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                    <div class="mb-3"><label class="form-label" for="noticeTitle">Title</label><input class="form-control" id="noticeTitle" name="title" maxlength="180" required></div>
                                    <div class="mb-3"><label class="form-label" for="noticeMessage">Message</label><textarea class="form-control" id="noticeMessage" name="message" rows="4" maxlength="10000" required></textarea></div>
                                    <button class="btn btn-dark" type="submit">Publish notice</button>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-xl-7">
                        <section class="card shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between"><h2 class="card-title h5 mb-0">Administrator audit log</h2><span class="text-secondary small">Latest 10 actions</span></div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th scope="col">Action</th><th scope="col">Actor</th><th scope="col">Details</th><th scope="col">Time</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($auditEntries as $entry): ?>
                                        <tr>
                                            <td><strong><?php echo $escape($entry['action']); ?></strong><br><small><?php echo $escape($entry['entity_type']); ?> #<?php echo (int) ($entry['entity_id'] ?? 0); ?></small></td>
                                            <td><?php echo $escape($entry['actor_name'] ?? 'System'); ?></td>
                                            <td><?php echo $escape((string) ($entry['details'] ?? '')); ?></td>
                                            <td class="text-nowrap"><?php echo $escape($entry['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$auditEntries): ?><tr><td colspan="4" class="text-center text-secondary py-4">No audited actions recorded yet.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                    <div class="col-12 col-xl-5">
                        <section class="card shadow-sm">
                            <div class="card-header"><h2 class="card-title h5 mb-0">Recent notices</h2></div>
                            <div class="card-body">
                                <?php foreach ($notices as $notice): ?>
                                    <article class="border-bottom pb-3 mb-3"><h3 class="h6 mb-1"><?php echo $escape($notice['title']); ?></h3><p class="mb-1"><?php echo nl2br($escape($notice['body'])); ?></p><small class="text-secondary"><?php echo $escape($notice['published_at']); ?></small></article>
                                <?php endforeach; ?>
                                <?php if (!$notices): ?><p class="text-secondary mb-0">No notices have been published yet.</p><?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <?php foreach ([['Recent feedback', $recentFeedback, 'feedback', 'feedback_id', 'submitter_name', 'submitter_email'], ['Contact messages', $recentContacts, 'contact', 'contact_message_id', 'full_name', 'email']] as [$heading, $messages, $type, $idField, $nameField, $emailField]): ?>
                    <div class="col-12 col-lg-6">
                        <section class="card shadow-sm">
                            <div class="card-header"><h2 class="card-title h5 mb-0"><?php echo $escape((string) $heading); ?></h2></div>
                            <div class="card-body">
                                <?php foreach ($messages as $message): ?>
                                    <article class="border-bottom pb-3 mb-3">
                                        <strong><?php echo $escape((string) ($message[$nameField] ?: 'Student')); ?></strong>
                                        <span class="text-secondary">· <?php echo $escape((string) ($message[$emailField] ?? '')); ?></span>
                                        <p class="my-2"><?php echo nl2br($escape($message['message'])); ?></p>
                                        <form action="actions.php?action=message-status" method="post" class="d-flex flex-wrap gap-2 align-items-center">
                                            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                            <input type="hidden" name="record_type" value="<?php echo $escape((string) $type); ?>">
                                            <input type="hidden" name="record_id" value="<?php echo (int) $message[$idField]; ?>">
                                            <label class="visually-hidden" for="<?php echo $escape((string) $type . (string) $message[$idField]); ?>">Message status</label>
                                            <select class="form-select form-select-sm w-auto" id="<?php echo $escape((string) $type . (string) $message[$idField]); ?>" name="status">
                                                <?php foreach (['new', 'reviewed', 'resolved'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $message['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option><?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">Update</button>
                                            <small class="text-secondary"><?php echo $escape($message['created_at']); ?></small>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                                <?php if (!$messages): ?><p class="text-secondary mb-0">Nothing to review.</p><?php endif; ?>
                            </div>
                        </section>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    <footer class="app-footer">Student Hub · AdminLTE 4 · Bootstrap 5</footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>
</body>
</html>
