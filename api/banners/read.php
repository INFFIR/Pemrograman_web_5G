<?php
// backend/api/banners/read.php
header('Content-Type: application/json');

// Atur CORS
header("Access-Control-Allow-Origin: http://localhost:8000"); // Ganti dengan domain frontend Anda
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight request
    exit(0);
}

require_once '../../includes/db_connect.php';

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Ambil semua banner
try {
    $stmt = $pdo->prepare("SELECT * FROM banners");
    $stmt->execute();
    $banners = $stmt->fetchAll();
    echo json_encode($banners);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
