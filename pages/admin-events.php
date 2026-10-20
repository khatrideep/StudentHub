<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_require_admin();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage Student Hub events and registration availability.">
    <title>Student Hub Admin - Events</title>
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
                <a class="btn btn-dark btn-sm ms-auto" href="logout.php">Sign out</a>
            </div>
        </nav>
        <aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
            <div class="sidebar-brand"><a href="admin.php" class="brand-link"><span class="brand-text fw-light">StudentHub</span></a></div>
            <div class="sidebar-wrapper">
                <nav>
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        <li class="nav-item"><a href="admin.php" class="nav-link"><i class="nav-icon bi bi-speedometer2"></i><p>Overview</p></a></li>
                        <li class="nav-item"><a href="admin-students.php" class="nav-link"><i class="nav-icon bi bi-people"></i><p>Students</p></a></li>
                        <li class="nav-item"><a href="admin-events.php" class="nav-link active" aria-current="page"><i class="nav-icon bi bi-calendar-event"></i><p>Events</p></a></li>
                        <li class="nav-item"><a href="notices.php" class="nav-link"><i class="nav-icon bi bi-megaphone"></i><p>Notices</p></a></li>
                        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon bi bi-house"></i><p>Student portal</p></a></li>
                    </ul>
                </nav>
            </div>
        </aside>
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-8"><h1 class="mb-1">Event management</h1><p class="text-secondary mb-0">Create and maintain campus events, posters, capacity, and registration status.</p></div>
                        <div class="col-sm-4 text-sm-end mt-3 mt-sm-0"><button type="button" class="btn btn-dark" id="newEventButton"><i class="bi bi-plus-lg me-1"></i>New event</button></div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    <div id="eventMessage" class="alert d-none" role="status" aria-live="polite"></div>
                    <div class="card shadow-sm mb-4" id="eventEditor" hidden>
                        <div class="card-header"><h2 class="card-title h5 mb-0" id="eventFormTitle">Create event</h2></div>
                        <div class="card-body">
                            <form id="eventForm" enctype="multipart/form-data">
                                <input type="hidden" name="id" id="eventId">
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="eventTitle" class="form-label">Title</label><input id="eventTitle" name="title" class="form-control" maxlength="180" required></div>
                                    <div class="col-md-3"><label for="eventCategory" class="form-label">Category</label><input id="eventCategory" name="category" class="form-control" maxlength="80" required></div>
                                    <div class="col-md-3"><label for="eventStatus" class="form-label">Registration</label><select id="eventStatus" name="status" class="form-select"><option value="open">Open</option><option value="closed">Closed</option></select></div>
                                    <div class="col-12"><label for="eventDescription" class="form-label">Description</label><textarea id="eventDescription" name="description" class="form-control" rows="3" maxlength="10000"></textarea></div>
                                    <div class="col-md-4"><label for="eventDate" class="form-label">Date and time</label><input id="eventDate" name="starts_at" type="datetime-local" class="form-control" required></div>
                                    <div class="col-md-4"><label for="eventLocation" class="form-label">Location</label><input id="eventLocation" name="location" class="form-control" maxlength="180" required></div>
                                    <div class="col-md-4"><label for="eventCapacity" class="form-label">Capacity <span class="text-secondary">(optional)</span></label><input id="eventCapacity" name="capacity" type="number" min="1" max="100000" class="form-control"></div>
                                    <div class="col-md-8"><label for="eventPoster" class="form-label">Poster <span class="text-secondary">(JPG, PNG, or WebP; up to 2 MB)</span></label><input id="eventPoster" name="poster" type="file" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
                                    <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-dark" id="saveEventButton">Save event</button><button type="button" class="btn btn-outline-secondary" id="cancelEventButton">Cancel</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <h2 class="card-title h5 mb-0">Events</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <label class="visually-hidden" for="eventSearch">Search events</label>
                                <input id="eventSearch" class="form-control form-control-sm" type="search" placeholder="Search events" autocomplete="off">
                                <label class="visually-hidden" for="eventCategoryFilter">Filter category</label>
                                <select id="eventCategoryFilter" class="form-select form-select-sm"><option value="">All categories</option></select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th scope="col">Event</th><th scope="col">Category</th><th scope="col">Date</th><th scope="col">Registrations</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                                <tbody id="eventTableBody"><tr><td colspan="6" class="text-center py-4">Loading events…</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <footer class="app-footer"><span>Student Hub · AdminLTE 4 · Bootstrap 5</span></footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>
    <script src="../js/admin-events.js"></script>
</body>
</html>
