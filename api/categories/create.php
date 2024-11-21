<?php
// backend/api/categories/create.php
header('Content-Type: application/json');

// Set CORS headers
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); // Update as needed
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight request
    exit(0);
}

require_once '../../includes/db_connect.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Determine Content-Type
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// Initialize data array
$data = [];

// Parse input based on Content-Type
if (strpos($contentType, 'application/json') !== false) {
    // Handle JSON input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid JSON',
            'json_error' => json_last_error_msg(),
            'raw_input' => $rawInput
        ]);
        exit();
    }
} elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
    // Handle URL-encoded form data
    $data = $_POST;
} else {
    // Unsupported Content-Type
    http_response_code(415);
    echo json_encode(['error' => 'Unsupported Media Type']);
    exit();
}

// Validate 'name'
if (!isset($data['name']) || empty(trim($data['name']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Category name is required']);
    exit();
}

$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';

// Insert into database
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
        // Optionally log the error
        error_log("Database Error: " . $e->getMessage());
    }
}
?>
