<?php
// backend/api/categories/create.php
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Ambil data JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Category name is required']);
    exit();
}

$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';

// Insert ke database
try {
    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    $id = $pdo->lastInsertId();
    echo json_encode(['message' => 'Category created', 'id' => $id]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        http_response_code(409);
        echo json_encode(['error' => 'Category name already exists']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
?>
