<?php
// backend/api/categories/update.php
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
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Ambil ID dari query parameter
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID is required']);
    exit();
}

$id = intval($_GET['id']);

// Ambil data JSON
$data = json_decode(file_get_contents('php://input'), true);

$name = isset($data['name']) ? trim($data['name']) : null;
$description = isset($data['description']) ? trim($data['description']) : null;

if (!$name && !$description) {
    http_response_code(400);
    echo json_encode(['error' => 'No data to update']);
    exit();
}

// Bangun query update
$fields = [];
$values = [];

if ($name) {
    $fields[] = 'name = ?';
    $values[] = $name;
}

if ($description) {
    $fields[] = 'description = ?';
    $values[] = $description;
}

$values[] = $id; // Untuk WHERE

$sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Category updated']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found or no change']);
    }
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
