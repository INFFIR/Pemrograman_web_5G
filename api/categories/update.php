<?php
// backend/api/categories/update.php
header('Content-Type: application/json');

// Set CORS headers
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); // Replace with your frontend domain
header("Access-Control-Allow-Methods: PUT, GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight request
    exit(0);
}

require_once '../../includes/db_connect.php';

// Only allow PUT method
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Retrieve ID from query parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID is required']);
    exit();
}

// Determine Content-Type
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// Initialize data array
$data = [];

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
    parse_str(file_get_contents("php://input"), $data);
} else {
    // Unsupported Content-Type
    http_response_code(415);
    echo json_encode(['error' => 'Unsupported Media Type']);
    exit();
}

// Validate input data
$name = isset($data['name']) && is_string($data['name']) ? trim($data['name']) : null;
$description = isset($data['description']) && is_string($data['description']) ? trim($data['description']) : null;

// If no data is sent to update
if (!$name && !$description) {
    http_response_code(400);
    echo json_encode(['error' => 'No data to update']);
    exit();
}

// Build dynamic update query based on received data
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

$values[] = $id; // Add ID at the end for WHERE clause

$sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Category updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found or no changes were made']);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        http_response_code(409);
        echo json_encode(['error' => 'Category name already exists']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'An internal error occurred', 'details' => $e->getMessage()]);
        // Log the database error
        error_log("Database Error: " . $e->getMessage());
    }
}
?>
