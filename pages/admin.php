<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_require_login();
if (($_SESSION['student_role'] ?? '') !== 'admin') {
    auth_redirect('dashboard.php');
}

$database = auth_database();
$studentCount = (int) $database->query("SELECT COUNT(*) AS total FROM students WHERE account_status = 'active'")->fetch_assoc()['total'];
$eventCount = (int) $database->query('SELECT COUNT(*) AS total FROM events WHERE starts_at >= NOW()')->fetch_assoc()['total'];
$noticeCount = (int) $database->query('SELECT COUNT(*) AS total FROM notices WHERE expires_at IS NULL OR expires_at >= NOW()')->fetch_assoc()['total'];
$feedbackCount = (int) $database->query("SELECT COUNT(*) AS total FROM feedback WHERE status = 'new'")->fetch_assoc()['total'];
$recentFeedback = $database->query(
    'SELECT f.feedback_id, f.category, f.submitter_name, f.submitter_email, f.subject, f.message, f.status, f.created_at
     FROM feedback f ORDER BY f.created_at DESC LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);
$recentContacts = $database->query(
    'SELECT contact_message_id, full_name, email, message, status, created_at
     FROM contact_messages ORDER BY created_at DESC LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);
$notices = $database->query(
    'SELECT title, body, published_at FROM notices ORDER BY published_at DESC LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);
$database->close();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Admin</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="../styles/site.css">
</head>
<body class="site-page">
    <div class="page-wrapper">
        <main class="main-content">
            <header class="navbar">
                <a href="../index.html" class="brand"><div class="logo"><img src="../logo.jpeg" alt="Student Hub Logo"></div>Student Hub</a>
                <ul class="nav-links">
                    <li><a href="home.html">Home</a></li><li><a href="events.php">Events</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li><li><a href="admin.php">Admin</a></li>
                    <li><a href="faqs.php">FAQs</a></li><li><a href="logout.php">Logout</a></li>
                </ul>
            </header>
            <h1>Admin Panel</h1>
            <p class="text-muted">Manage notices and review messages stored in the Student Hub database.</p>
            <?php if (isset($_GET['notice'])): ?><p class="auth-message" role="status">The notice was published.</p><?php endif; ?>
            <?php if (isset($_GET['status'])): ?><p class="auth-message" role="status">The message status was updated.</p><?php endif; ?>
            <?php if (isset($_GET['error'])): ?><p class="auth-message" role="alert">We could not complete that action. Please check the form and try again.</p><?php endif; ?>
            <div class="grid-4" style="margin-bottom: 16px;">
                <div class="card"><small>Active students</small><strong style="font-size:1.8rem;"><?php echo $studentCount; ?></strong></div>
                <div class="card"><small>Upcoming events</small><strong style="font-size:1.8rem;"><?php echo $eventCount; ?></strong></div>
                <div class="card"><small>Active notices</small><strong style="font-size:1.8rem;"><?php echo $noticeCount; ?></strong></div>
                <div class="card"><small>New feedback</small><strong style="font-size:1.8rem;"><?php echo $feedbackCount; ?></strong></div>
            </div>
            <div class="grid-2">
                <section class="panel">
                    <h2>Publish notice</h2>
                    <form action="actions.php?action=notice" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $escape(auth_csrf_token()); ?>">
                        <div class="form-group"><label for="noticeTitle">Title</label><input id="noticeTitle" name="title" type="text" maxlength="180" required></div>
                        <div class="form-group"><label for="noticeMessage">Message</label><textarea id="noticeMessage" name="message" maxlength="10000" required></textarea></div>
                        <button type="submit" class="btn">Publish</button>
                    </form>
                    <h2>Recent notices</h2>
                    <?php foreach ($notices as $notice): ?>
                        <article><h3><?php echo $escape($notice['title']); ?></h3><p><?php echo nl2br($escape($notice['body'])); ?></p></article>
                    <?php endforeach; ?>
                    <?php if (!$notices): ?><p>No notices have been published yet.</p><?php endif; ?>
                </section>
                <section class="panel">
                    <h2>Recent feedback</h2>
                    <?php foreach ($recentFeedback as $item): ?>
                        <article class="card">
                            <strong><?php echo $escape($item['category']); ?> · <?php echo $escape($item['status']); ?></strong>
                            <p><?php echo $escape($item['submitter_name'] ?? 'Student'); ?> · <?php echo $escape($item['submitter_email'] ?? ''); ?></p>
                            <p><?php echo nl2br($escape($item['message'])); ?></p>
                            <small><?php echo $escape($item['created_at']); ?></small>
                            <form action="actions.php?action=message-status" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $escape(auth_csrf_token()); ?>">
                                <input type="hidden" name="record_type" value="feedback">
                                <input type="hidden" name="record_id" value="<?php echo (int) $item['feedback_id']; ?>">
                                <label>Status <select name="status"><option value="new" <?php echo $item['status'] === 'new' ? 'selected' : ''; ?>>New</option><option value="reviewed" <?php echo $item['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option><option value="resolved" <?php echo $item['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option></select></label>
                                <button type="submit" class="btn">Update</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$recentFeedback): ?><p>No feedback has been submitted yet.</p><?php endif; ?>
                </section>
            </div>
            <section class="panel">
                <h2>Contact messages</h2>
                <?php foreach ($recentContacts as $item): ?>
                    <article class="card">
                        <strong><?php echo $escape($item['full_name']); ?> · <?php echo $escape($item['email']); ?></strong>
                        <p><?php echo nl2br($escape($item['message'])); ?></p>
                        <small><?php echo $escape($item['status']); ?> · <?php echo $escape($item['created_at']); ?></small>
                        <form action="actions.php?action=message-status" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $escape(auth_csrf_token()); ?>">
                            <input type="hidden" name="record_type" value="contact">
                            <input type="hidden" name="record_id" value="<?php echo (int) $item['contact_message_id']; ?>">
                            <label>Status <select name="status"><option value="new" <?php echo $item['status'] === 'new' ? 'selected' : ''; ?>>New</option><option value="reviewed" <?php echo $item['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option><option value="resolved" <?php echo $item['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option></select></label>
                            <button type="submit" class="btn">Update</button>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if (!$recentContacts): ?><p>No contact messages have been received yet.</p><?php endif; ?>
            </section>
        </main>
        <footer class="footer"><p>&copy; 2026 Student Hub &middot; <a href="contact.html">Contact Us</a></p></footer>
    </div>
</body>
</html>
