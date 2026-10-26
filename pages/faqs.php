<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';
auth_start_session();

$faqJson = file_get_contents(__DIR__ . '/../data/faqs.json');
if ($faqJson === false) {
    throw new RuntimeException('Unable to read the FAQ data file.');
}

$faqs = json_decode($faqJson, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($faqs) || count($faqs) < 5) {
    throw new RuntimeException('The FAQ data must contain at least five questions.');
}
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
                <p class="text-muted">Select a question to see its answer.</p>
                <div class="faq-grid">
                    <?php foreach ($faqs as $faq): ?>
                        <?php
                        if (!is_array($faq) || !isset($faq['question'], $faq['answer']) || !is_string($faq['question']) || !is_string($faq['answer'])) {
                            throw new RuntimeException('The FAQ data contains an invalid question or answer.');
                        }
                        ?>
                        <details class="card faq-question">
                            <summary class="faq-trigger"><?= htmlspecialchars($faq['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></summary>
                            <p class="text-muted faq-answer"><?= htmlspecialchars($faq['answer'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
        <footer class="footer"><p>&copy; 2026 Student Hub &middot; <a href="contact.html">Contact Us</a></p></footer>
    </div>
    <script src="../js/theme.js"></script>
</body>
</html>
