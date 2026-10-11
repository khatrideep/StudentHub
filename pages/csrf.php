<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

auth_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode(['csrfToken' => auth_csrf_token()], JSON_THROW_ON_ERROR);
