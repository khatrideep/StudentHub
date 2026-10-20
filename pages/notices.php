<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
$database = null;
$notices = [];
$noticesUnavailable = false;
try {
    $database = auth_database();
    $notices = $database->query(
        'SELECT title, body, published_at
         FROM notices
         WHERE expires_at IS NULL OR expires_at >= NOW()
         ORDER BY published_at DESC'
    )->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $error) {
    error_log('Unable to load current Student Hub notices: ' . $error->getMessage());
    http_response_code(503);
    $noticesUnavailable = true;
} finally {
    if ($database instanceof mysqli) {
        $database->close();
    }
}
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Read the latest Student Hub campus and academic notices.">
    <title>Student Hub - Notices</title>
    <link rel="stylesheet" href="../styles/notices.css">
    <link rel="stylesheet" href="../styles/site.css">
</head>
<body class="site-page">
    <div class="page-wrapper">
        <main class="main-content">
            <header class="navbar">
                <a href="../index.html" class="brand"><div class="logo"><img src="../logo.jpeg" alt="Student Hub Logo"></div>Student Hub</a>
                <button class="theme-toggle" type="button" aria-label="Switch to dark theme" aria-pressed="false">Dark mode</button>
                <ul class="nav-links">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="notices.php" aria-current="page">Notices</a></li>
                    <li><a href="students.html">Students</a></li>
                    <li><a href="dashboard.php#profile">Profile</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if (($_SESSION['student_role'] ?? '') === 'admin'): ?><li><a href="admin.php">Admin</a></li><?php endif; ?>
                    <li><a href="feedback.html">Feedback</a></li>
                    <li><a href="faqs.php">FAQs</a></li>
                    <?php if (empty($_SESSION['student_id'])): ?><li><a href="login.html">Login</a></li><li><a href="register.html">Register</a></li><?php else: ?><li><a href="logout.php">Logout</a></li><?php endif; ?>
                </ul>
            </header>

            <section class="notices-heading">
                <p class="eyebrow">Stay up to date</p>
                <h1>Campus Notices</h1>
                <p class="text-muted">Important academic reminders and campus announcements in one place.</p>
            </section>

            <section class="notice-list" aria-label="Current notices">
                <?php if ($noticesUnavailable): ?>
                    <p class="empty-notices" role="alert">Notices are temporarily unavailable. Please try again later.</p>
                <?php else: ?>
                    <?php foreach ($notices as $notice): ?>
                    <article class="card notice-card">
                        <div class="notice-meta">
                            <span class="notice-label">Announcement</span>
                            <time datetime="<?php echo $escape(date('c', strtotime($notice['published_at']))); ?>">
                                <?php echo $escape(date('F j, Y', strtotime($notice['published_at']))); ?>
                            </time>
                        </div>
                        <h2><?php echo $escape($notice['title']); ?></h2>
                        <p><?php echo nl2br($escape($notice['body'])); ?></p>
                    </article>
                    <?php endforeach; ?>
                <?php if (!$notices): ?>
                    <p class="empty-notices">There are no current notices. Please check back later.</p>
                <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
        <footer class="footer"><p>&copy; 2026 Student Hub &middot; <a href="contact.html">Contact Us</a></p></footer>
    </div>
    <script src="../js/theme.js"></script>
</body>
</html>
