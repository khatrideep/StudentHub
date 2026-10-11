<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
$database = auth_database();
$events = $database->query(
    "SELECT e.event_id, e.title, e.description, e.starts_at, e.location, e.capacity,
            COUNT(CASE WHEN r.registration_status = 'registered' THEN 1 END) AS registration_count,
            MAX(CASE WHEN r.student_id = " . (int) ($_SESSION['student_id'] ?? 0) . " AND r.registration_status = 'registered' THEN 1 ELSE 0 END) AS is_registered
     FROM events e
     LEFT JOIN event_registrations r ON r.event_id = e.event_id
     WHERE e.starts_at >= NOW()
     GROUP BY e.event_id
     ORDER BY e.starts_at"
)->fetch_all(MYSQLI_ASSOC);
$database->close();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Events</title>
    <link rel="stylesheet" href="../styles/events.css">
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
            <h1>Campus Events</h1>
            <p class="text-muted">Explore upcoming student activities, workshops, and cultural events.</p>
            <?php if (isset($_GET['registered'])): ?>
                <p class="auth-message" role="status"><?php echo $_GET['registered'] === 'already' ? 'You are already registered for this event.' : 'Your event registration is confirmed.'; ?></p>
            <?php elseif (isset($_GET['error'])): ?>
                <p class="auth-message" role="alert">We could not complete that request. Please try again or sign in.</p>
            <?php endif; ?>
            <div class="grid-3">
                <?php foreach ($events as $event): ?>
                    <article class="card">
                        <span class="tag"><?php echo $escape(date('M j, Y', strtotime($event['starts_at']))); ?></span>
                        <h2><?php echo $escape($event['title']); ?></h2>
                        <p><?php echo nl2br($escape($event['description'] ?? '')); ?></p>
                        <p class="text-muted"><?php echo $escape($event['location'] ?? 'Location to be announced'); ?></p>
                        <?php if ($event['capacity'] !== null): ?>
                            <p class="text-muted"><?php echo max(0, (int) $event['capacity'] - (int) $event['registration_count']); ?> places remaining</p>
                        <?php endif; ?>
                        <?php if ((int) $event['is_registered'] === 1): ?>
                            <span class="btn" aria-label="Registration confirmed">Registered</span>
                        <?php elseif (empty($_SESSION['student_id'])): ?>
                            <a class="btn" href="login.html">Log in to RSVP</a>
                        <?php else: ?>
                            <form method="post" action="actions.php?action=event-register" data-csrf>
                                <input type="hidden" name="event_id" value="<?php echo (int) $event['event_id']; ?>">
                                <button type="submit" class="btn">RSVP</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$events): ?><p>No upcoming events are scheduled right now.</p><?php endif; ?>
        </main>
    </div>
    <script src="../js/form-security.js"></script>
</body>
</html>
