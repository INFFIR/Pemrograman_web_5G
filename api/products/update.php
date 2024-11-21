<?php
// backend/api/products/update.php
header('Content-Type: application/json');

// Set CORS headers
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); // Replace with your frontend domain
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../includes/db_connect.php';

// Define UPLOAD_DIR if not already defined
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
}

// Function to handle image uploads
function handleImageUpload($inputName, &$debugLog) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $fileTmpPath = $_FILES[$inputName]['tmp_name'];
        $fileName = basename($_FILES[$inputName]['name']);
        $uploadFileDir = UPLOAD_DIR . 'products/';
        return handleImageUploadProcess($fileTmpPath, $fileName, $uploadFileDir, $debugLog, "file upload");
    } elseif (isset($_POST['image_url']) && filter_var($_POST['image_url'], FILTER_VALIDATE_URL)) {
        // Handle image URL
        $image_url = $_POST['image_url'];
        $debugLog .= "Processing image_url: " . $image_url . "\n";
        return handleImageUrl($image_url, $debugLog);
    } elseif (isset($_POST['image_path']) && !empty($_POST['image_path'])) {
        // Handle local image path
        $local_image_path = $_POST['image_path'];
        $debugLog .= "Processing image_path: " . $local_image_path . "\n";
        return handleImagePath($local_image_path, $debugLog);
    } else {
        // No image provided
        $debugLog .= "No image provided.\n";
        return null;
    }
}

function handleImageUploadProcess($fileTmpPath, $fileName, $uploadFileDir, &$debugLog, $source) {
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate image extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format']);
        exit();
    }

    // Generate unique file name
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;

    // Ensure upload directory exists
    if (!is_dir($uploadFileDir)) {
        if (!mkdir($uploadFileDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit();
        }
    }

    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        $relativePath = 'uploads/products/' . $newFileName;
        $debugLog .= "Image uploaded via {$source}: " . $relativePath . "\n";
        return $relativePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

function handleImageUrl($image_url, &$debugLog) {
    // Fetch image data from URL
    $imageData = @file_get_contents($image_url);
    if ($imageData === FALSE) {
        http_response_code(400);
        echo json_encode(['error' => 'Unable to fetch image from URL']);
        exit();
    }
    $debugLog .= "Image fetched successfully from URL.\n";

    // Determine file name and extension
    $fileName = basename(parse_url($image_url, PHP_URL_PATH));
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate image extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format from URL']);
        exit();
    }

    // Generate unique file name
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'products/';
    $dest_path = $uploadFileDir . $newFileName;

    // Ensure upload directory exists
    if (!is_dir($uploadFileDir)) {
        if (!mkdir($uploadFileDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit();
        }
    }

    // Save image to upload directory
    if (file_put_contents($dest_path, $imageData) !== FALSE) {
        $relativePath = 'uploads/products/' . $newFileName;
        $debugLog .= "Image saved from URL: " . $relativePath . "\n";
        return $relativePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error saving the image from URL']);
        exit();
    }
}

function handleImagePath($local_image_path, &$debugLog) {
    // Ensure the local image file exists
    if (!file_exists($local_image_path)) {
        http_response_code(400);
        echo json_encode(['error' => 'Local image file does not exist']);
        exit();
    }

    // Determine file extension
    $fileExtension = strtolower(pathinfo($local_image_path, PATHINFO_EXTENSION));

    // Validate image extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format from local path']);
        exit();
    }

    // Generate unique file name
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'products/';
    $dest_path = $uploadFileDir . $newFileName;

    // Ensure upload directory exists
    if (!is_dir($uploadFileDir)) {
        if (!mkdir($uploadFileDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit();
        }
    }

    // Copy image to upload directory
    if (copy($local_image_path, $dest_path)) {
        $relativePath = 'uploads/products/' . $newFileName;
        $debugLog .= "Image copied from local path: " . $relativePath . "\n";
        return $relativePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error copying the image from local path']);
        exit();
    }
}

// Initialize debug log
$debugLog = "Update Request Received: " . date('Y-m-d H:i:s') . "\n";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Retrieve the product ID from the query parameter
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit();
}

$id = intval($_GET['id']);
$debugLog .= "Product ID: " . $id . "\n";

// Handle image upload (if any)
$image_path = handleImageUpload('image', $debugLog);

// Retrieve and sanitize input data
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$price = isset($_POST['price']) ? floatval(str_replace(',', '', $_POST['price'])) : null;
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

$debugLog .= "Input Data - Name: {$name}, Description: {$description}, Price: {$price}, Category ID: {$category_id}\n";

// Validate required fields (if necessary)
if ($name === null && $description === null && $price === null && $category_id === null && $image_path === null) {
    http_response_code(400);
    echo json_encode(['error' => 'No data to update']);
    exit();
}

// Build the SQL UPDATE query dynamically
$fields = [];
$values = [];

if ($name !== null) {
    $fields[] = 'name = ?';
    $values[] = $name;
}

if ($description !== null) {
    $fields[] = 'description = ?';
    $values[] = $description;
}

if ($price !== null) {
    $fields[] = 'price = ?';
    $values[] = $price;
}

if ($image_path !== null) {
    $fields[] = 'image_path = ?';
    $values[] = $image_path;
}

if ($category_id !== null) {
    $fields[] = 'category_id = ?';
    $values[] = $category_id;
}

if (empty($fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid data provided for update']);
    exit();
}

$values[] = $id; // For WHERE clause

$sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    if ($stmt->rowCount() > 0) {
        $debugLog .= "Product updated successfully.\n";
        echo json_encode(['message' => 'Product updated', 'image_path' => $image_path]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found or no changes made']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred', 'details' => $e->getMessage()]);
    $debugLog .= "Database Error: " . $e->getMessage() . "\n";
}

// Write debug log to file
file_put_contents('debug.log', $debugLog . "\n", FILE_APPEND);
?>
