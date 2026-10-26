<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
$database = auth_database();
$events = $database->query(
    "SELECT e.event_id, e.title, e.description, e.category, e.status, e.poster_path, e.starts_at, e.location, e.capacity,
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
    <link rel="stylesheet" href="../styles/events.css?v=3">
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
            <section class="sample-events-section" aria-labelledby="sampleEventsHeading">
                <div class="events-section-heading">
                    <div>
                        <h2 id="sampleEventsHeading">Upcoming events</h2>
                        <p class="text-muted">Browse events from our events list. Search, filter, or sort to find one.</p>
                    </div>
                </div>
                <div class="event-controls" aria-label="Search and filter events">
                    <div class="event-control event-search">
                        <label for="eventSearch">Search events</label>
                        <input id="eventSearch" type="search" placeholder="Search title, venue, or details" autocomplete="off">
                    </div>
                    <div class="event-control">
                        <label for="eventCategory">Category</label>
                        <select id="eventCategory">
                            <option value="">All categories</option>
                        </select>
                    </div>
                    <div class="event-control">
                        <label for="eventSort">Sort by</label>
                        <select id="eventSort">
                            <option value="date-asc">Date: soonest first</option>
                            <option value="date-desc">Date: latest first</option>
                            <option value="title-asc">Title: A to Z</option>
                            <option value="seats-asc">Places: fewest first</option>
                            <option value="seats-desc">Places: most first</option>
                        </select>
                    </div>
                </div>
                <p id="sampleEventStatus" class="event-status" role="status" aria-live="polite" aria-busy="true">Loading events…</p>
                <div id="sampleEventList" class="grid-3" aria-label="Events loaded from the events JSON file"></div>
                <nav id="eventPagination" class="event-pagination" aria-label="Event pages" hidden></nav>
            </section>

            <?php if ($events): ?>
                <section class="live-events-section" aria-labelledby="liveEventsHeading">
                    <h2 id="liveEventsHeading">Live events and registration</h2>
                    <p class="text-muted">These events are managed by Student Hub and support RSVP registration.</p>
                <div class="grid-3">
                    <?php foreach ($events as $event): ?>
                        <article class="card">
                            <?php if (!empty($event['poster_path'])): ?><img class="event-poster" src="../<?php echo $escape($event['poster_path']); ?>" alt="<?php echo $escape($event['title']); ?> event poster" loading="lazy"><?php endif; ?>
                            <span class="tag"><?php echo $escape(date('M j, Y', strtotime($event['starts_at']))); ?></span>
                            <span class="tag"><?php echo $escape($event['category']); ?></span>
                            <h2><?php echo $escape($event['title']); ?></h2>
                            <p><?php echo nl2br($escape($event['description'] ?? '')); ?></p>
                            <p class="text-muted"><?php echo $escape($event['location'] ?? 'Location to be announced'); ?></p>
                            <?php if ($event['capacity'] !== null): ?>
                                <p class="text-muted"><?php echo max(0, (int) $event['capacity'] - (int) $event['registration_count']); ?> places remaining</p>
                            <?php endif; ?>
                            <?php if ($event['status'] !== 'open'): ?>
                                <span class="btn" aria-label="Registration is closed">Registration closed</span>
                            <?php elseif ((int) $event['is_registered'] === 1): ?>
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
                </section>
            <?php else: ?>
                <section class="live-events-section" aria-labelledby="liveEventsHeading">
                    <h2 id="liveEventsHeading">Live event registration</h2>
                    <p class="text-muted">There are no live events available for RSVP right now. The events above are sample listings and do not accept registrations.</p>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <script src="../js/form-security.js"></script>
    <script src="../js/theme.js"></script>
    <script src="../js/events.js"></script>
</body>
</html>
