<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_require_admin();
$database = auth_database();
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$search = trim((string) ($_GET['q'] ?? ''));
$filterStatus = (string) ($_GET['status'] ?? 'active');
if (!in_array($filterStatus, ['active', 'inactive', 'all'], true)) {
    $filterStatus = 'active';
}
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$pageSize = 20;
$where = "role = 'student' AND account_status IN ('active', 'inactive')";
$types = '';
$parameters = [];
if ($filterStatus !== 'all') {
    $where .= ' AND account_status = ?';
    $types .= 's';
    $parameters[] = $filterStatus;
}
if ($search !== '') {
    $where .= ' AND (full_name LIKE ? OR email LIKE ? OR program LIKE ?)';
    $types .= 'sss';
    $like = '%' . $search . '%';
    array_push($parameters, $like, $like, $like);
}

if (isset($_GET['export'])) {
    $export = $database->prepare(
        "SELECT student_code, full_name, email, phone, program, academic_year, account_status
         FROM students WHERE {$where} ORDER BY full_name"
    );
    if ($types !== '') {
        auth_bind_parameters($export, $types, $parameters);
    }
    $export->execute();
    $rows = $export->get_result();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="studenthub-students.csv"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['Student code', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Status']);
    while ($row = $rows->fetch_row()) {
        $safeRow = array_map(
            static fn ($value): string => preg_match('/^[\s]*[=+\-@]/', (string) $value) ? "'" . (string) $value : (string) ($value ?? ''),
            $row
        );
        fputcsv($output, $safeRow);
    }
    fclose($output);
    $export->close();
    $database->close();
    exit;
}

$count = $database->prepare("SELECT COUNT(*) AS total FROM students WHERE {$where}");
if ($types !== '') {
    auth_bind_parameters($count, $types, $parameters);
}
$count->execute();
$total = (int) $count->get_result()->fetch_assoc()['total'];
$count->close();
$pageCount = max(1, (int) ceil($total / $pageSize));
$page = min($page, $pageCount);
$offset = ($page - 1) * $pageSize;
$list = $database->prepare(
    "SELECT student_id, student_code, full_name, email, phone, program, academic_year, role, account_status
     FROM students WHERE {$where}
     ORDER BY full_name LIMIT ? OFFSET ?"
);
$listTypes = $types . 'ii';
$listParameters = [...$parameters, $pageSize, $offset];
auth_bind_parameters($list, $listTypes, $listParameters);
$list->execute();
$students = $list->get_result()->fetch_all(MYSQLI_ASSOC);
$list->close();
$editing = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $edit = $database->prepare(
        "SELECT student_id, full_name, email, phone, program, academic_year
         FROM students WHERE student_id = ? AND role = 'student' LIMIT 1"
    );
    $edit->bind_param('i', $editId);
    $edit->execute();
    $editing = $edit->get_result()->fetch_assoc() ?: null;
    $edit->close();
}
$csrfToken = auth_csrf_token();
$database->close();
$queryString = static fn (int $targetPage): string => http_build_query([
    'q' => $search,
    'status' => $filterStatus,
    'page' => $targetPage,
]);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Search, export, and manage student accounts.">
    <title>Student Hub Admin - Students</title>
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
            <div class="sidebar-wrapper"><nav><ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                <li class="nav-item"><a href="admin.php" class="nav-link"><i class="nav-icon bi bi-speedometer2"></i><p>Overview</p></a></li>
                <li class="nav-item"><a href="admin-students.php" class="nav-link active" aria-current="page"><i class="nav-icon bi bi-people"></i><p>Students</p></a></li>
                <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="nav-icon bi bi-calendar-event"></i><p>Events</p></a></li>
                <li class="nav-item"><a href="notices.php" class="nav-link"><i class="nav-icon bi bi-megaphone"></i><p>Notices</p></a></li>
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon bi bi-house"></i><p>Student portal</p></a></li>
            </ul></nav></div>
        </aside>
        <main class="app-main">
            <div class="app-content-header"><div class="container-fluid">
                <div class="row align-items-center"><div class="col-sm-8"><h1 class="mb-1">Student management</h1><p class="text-secondary mb-0">Search, edit, export, and deactivate student accounts.</p></div>
                <div class="col-sm-4 text-sm-end mt-3 mt-sm-0"><a class="btn btn-dark" href="?<?php echo $escape($queryString($page)); ?>&export=1"><i class="bi bi-download me-1"></i>Export CSV</a></div></div>
            </div></div>
            <div class="app-content"><div class="container-fluid">
                <?php if (isset($_GET['saved'])): ?><p class="alert" role="status">Student details were saved.</p><?php elseif (isset($_GET['status_updated'])): ?><p class="alert" role="status">Student account status was updated.</p><?php elseif (isset($_GET['error'])): ?><p class="alert" role="alert">We could not save that student. Check the details and try again.</p><?php endif; ?>
                <section class="card shadow-sm mb-4" aria-labelledby="studentFormHeading">
                    <div class="card-header"><h2 class="card-title h5 mb-0" id="studentFormHeading"><?php echo $editing ? 'Edit student' : 'Add student'; ?></h2></div>
                    <div class="card-body">
                        <form action="actions.php?action=student-save" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                            <input type="hidden" name="student_id" value="<?php echo (int) ($editing['student_id'] ?? 0); ?>">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label" for="studentName">Full name</label><input class="form-control" id="studentName" name="full_name" maxlength="100" required value="<?php echo $escape($editing['full_name'] ?? ''); ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="studentEmail">Email</label><input class="form-control" id="studentEmail" name="email" type="email" maxlength="254" required value="<?php echo $escape($editing['email'] ?? ''); ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="studentPhone">Phone</label><input class="form-control" id="studentPhone" name="phone" type="tel" maxlength="30" value="<?php echo $escape($editing['phone'] ?? ''); ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="studentProgram">Course</label><input class="form-control" id="studentProgram" name="program" maxlength="120" required value="<?php echo $escape($editing['program'] ?? ''); ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="studentYear">Academic year</label><select class="form-select" id="studentYear" name="academic_year" required>
                                    <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year): ?><option value="<?php echo $escape($year); ?>" <?php echo ($editing['academic_year'] ?? '') === $year ? 'selected' : ''; ?>><?php echo $escape($year); ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-md-4"><label class="form-label" for="studentPassword"><?php echo $editing ? 'Set new password (optional)' : 'Initial password'; ?></label><input class="form-control" id="studentPassword" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" <?php echo $editing ? '' : 'required'; ?>><div class="form-text"><?php echo $editing ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.'; ?></div></div>
                                <div class="col-12 d-flex gap-2"><button class="btn btn-dark" type="submit"><?php echo $editing ? 'Save changes' : 'Create student'; ?></button><?php if ($editing): ?><a href="admin-students.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?></div>
                            </div>
                        </form>
                    </div>
                </section>
                <section class="card shadow-sm">
                    <div class="card-header"><h2 class="card-title h5 mb-0">Student accounts <span class="text-secondary fw-normal">(<?php echo $total; ?>)</span></h2></div>
                    <div class="card-body border-bottom">
                        <form class="row g-2" method="get">
                            <div class="col-md-8"><label class="visually-hidden" for="studentSearch">Search students</label><input id="studentSearch" class="form-control" name="q" type="search" value="<?php echo $escape($search); ?>" placeholder="Search name, email, or course"></div>
                            <div class="col-md-2"><label class="visually-hidden" for="accountStatus">Account status</label><select id="accountStatus" class="form-select" name="status"><option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All</option></select></div>
                            <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary" type="submit">Search</button></div>
                        </form>
                    </div>
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th scope="col">Student</th><th scope="col">Course / year</th><th scope="col">Contact</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo $escape($student['full_name']); ?></strong><br><small><?php echo $escape($student['student_code']); ?> · <?php echo $escape($student['role']); ?></small></td>
                                    <td><?php echo $escape($student['program'] ?? 'Not provided'); ?><br><small><?php echo $escape($student['academic_year'] ?? 'Not provided'); ?></small></td>
                                    <td><a href="mailto:<?php echo $escape($student['email']); ?>"><?php echo $escape($student['email']); ?></a><br><small><?php echo $escape($student['phone'] ?? 'No phone'); ?></small></td>
                                    <td><span class="status-pill"><?php echo $escape(ucfirst($student['account_status'])); ?></span></td>
                                    <td class="text-nowrap">
                                        <?php if ($student['role'] === 'student'): ?><a class="btn btn-outline-secondary btn-sm" href="?<?php echo $escape(http_build_query(['q' => $search, 'status' => $filterStatus, 'page' => $page, 'edit' => $student['student_id']])); ?>">Edit</a><?php endif; ?>
                                        <?php if ((int) $student['student_id'] !== (int) $_SESSION['student_id']): ?>
                                            <form class="d-inline" action="actions.php?action=student-status" method="post" onsubmit="return confirm('Change this account status?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                                <input type="hidden" name="student_id" value="<?php echo (int) $student['student_id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $student['account_status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button class="btn btn-outline-secondary btn-sm" type="submit"><?php echo $student['account_status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$students): ?><tr><td colspan="5" class="text-center text-secondary py-4">No student accounts match this search.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                    <?php if ($pageCount > 1): ?><div class="card-footer d-flex justify-content-between align-items-center">
                        <span>Page <?php echo $page; ?> of <?php echo $pageCount; ?></span>
                        <div class="d-flex gap-2">
                            <?php if ($page > 1): ?><a class="btn btn-outline-secondary btn-sm" href="?<?php echo $escape($queryString($page - 1)); ?>">Previous</a><?php endif; ?>
                            <?php if ($page < $pageCount): ?><a class="btn btn-outline-secondary btn-sm" href="?<?php echo $escape($queryString($page + 1)); ?>">Next</a><?php endif; ?>
                        </div>
                    </div><?php endif; ?>
                </section>
            </div></div>
        </main>
        <footer class="app-footer">Student Hub · AdminLTE 4 · Bootstrap 5</footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>
</body>
</html>
