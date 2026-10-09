<?php
// api/slots.php
require_once __DIR__ . '/config.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT date, time, package_name FROM bookings ORDER BY date ASC, time ASC");
    $slots = $stmt->fetchAll();
    jsonResponse(['data' => $slots]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Gagal mengambil data slot: ' . $e->getMessage()], 500);
}
