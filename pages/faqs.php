<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
$database = auth_database();
$faqs = $database->query('SELECT question, answer FROM faq_entries ORDER BY sort_order, faq_id')->fetch_all(MYSQLI_ASSOC);
$database->close();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - FAQs</title>
    <link rel="stylesheet" href="../styles/faqs.css">
    <link rel="stylesheet" href="../styles/site.css">
</head>
<body class="site-page">
    <div class="page-wrapper">
        <main class="main-content">
            <header class="navbar">
                <a href="../index.html" class="brand"><div class="logo"><img src="../logo.jpeg" alt="Student Hub Logo"></div>Student Hub</a>
                <ul class="nav-links">
                    <li><a href="home.html">Home</a></li><li><a href="about.html">About</a></li>
                    <li><a href="events.php">Events</a></li><li><a href="dashboard.php">Dashboard</a></li>
                    <?php if (($_SESSION['student_role'] ?? '') === 'admin'): ?><li><a href="admin.php">Admin</a></li><?php endif; ?>
                    <li><a href="feedback.html">Feedback</a></li><li><a href="faqs.php">FAQs</a></li>
                    <?php if (empty($_SESSION['student_id'])): ?><li><a href="login.html">Login</a></li><li><a href="register.html">Register</a></li><?php else: ?><li><a href="logout.php">Logout</a></li><?php endif; ?>
                </ul>
            </header>
            <h1>Frequently Asked Questions</h1>
            <div class="faq-grid">
                <?php foreach ($faqs as $faq): ?>
                    <article class="card faq-question" role="button" tabindex="0" aria-expanded="false">
                        <strong><?php echo $escape($faq['question']); ?></strong>
                        <p class="text-muted faq-answer" style="margin-top:4px;"><?php echo $escape($faq['answer']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>
        <footer class="footer"><p>&copy; 2026 Student Hub &middot; <a href="contact.html">Contact Us</a></p></footer>
    </div>
    <script src="../js/faqs.js"></script>
</body>
</html>
