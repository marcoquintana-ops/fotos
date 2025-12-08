<?php
// process/get_events_ajax.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    $sp = SP_GET_EVENTS;
    $stmt = $pdo->query("CALL {$sp}()");
    $events = $stmt->fetchAll();
    $stmt->closeCursor();
    echo json_encode($events);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
