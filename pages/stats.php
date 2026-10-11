<?php
declare(strict_types=1);

require_once __DIR__ . '/auth-db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $database = auth_database();
    $summary = $database->query(
        "SELECT
            (SELECT COUNT(*) FROM events WHERE starts_at >= NOW()) AS upcoming_events,
            (SELECT COUNT(*) FROM notices WHERE expires_at IS NULL OR expires_at >= NOW()) AS active_notices,
            (SELECT MIN(starts_at) FROM events WHERE starts_at >= NOW()) AS next_event"
    )->fetch_assoc();
    $database->close();
    echo json_encode($summary, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('Student Hub home summary error: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'summary_unavailable'], JSON_THROW_ON_ERROR);
}
