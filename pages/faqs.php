<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';
auth_start_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Find answers to common Student Hub account, event, and campus questions.">
    <title>Student Hub - FAQs</title>
    <link rel="stylesheet" href="../styles/faqs.css">
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
                    <li><a href="students.html">Students</a></li><li><a href="dashboard.php#profile">Profile</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <?php if (($_SESSION['student_role'] ?? '') === 'admin'): ?><li><a href="admin.php">Admin</a></li><?php endif; ?>
                    <li><a href="feedback.html">Feedback</a></li><li><a href="faqs.php" aria-current="page">FAQs</a></li>
                    <?php if (empty($_SESSION['student_id'])): ?><li><a href="login.html">Login</a></li><li><a href="register.html">Register</a></li><?php else: ?><li><a href="logout.php">Logout</a></li><?php endif; ?>
                </ul>
            </header>
            <section aria-labelledby="faqHeading">
                <h1 id="faqHeading">Frequently Asked Questions</h1>
                <p class="text-muted">Search the help topics or open a question to see its answer.</p>
                <div class="faq-controls">
                    <label for="faqSearch">Search questions</label>
                    <input id="faqSearch" type="search" placeholder="Search accounts, events, support…" autocomplete="off">
                </div>
                <p id="faqStatus" role="status" aria-live="polite">Loading questions…</p>
                <div id="faqList" class="faq-grid" aria-busy="true"></div>
            </section>
        </main>
        <footer class="footer"><p>&copy; 2026 Student Hub &middot; <a href="contact.html">Contact Us</a></p></footer>
    </div>
    <script src="../js/faqs.js"></script>
    <script src="../js/theme.js"></script>
</body>
</html>
