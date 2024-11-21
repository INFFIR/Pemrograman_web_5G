<?php
// backend/api/banners/update.php
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

/**
 * Handle image upload from different sources.
 *
 * @param string $inputName Name of the file input.
 * @param string $requestMethod HTTP request method.
 * @param string &$debugLog Reference to the debug log string.
 * @return string|null Relative path to the uploaded image or null.
 */
function handleImageUpload($inputName, $requestMethod, &$debugLog) {
    if ($requestMethod === 'POST') {
        // Handle image uploads only for POST requests
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
            // Handle file upload
            $fileTmpPath = $_FILES[$inputName]['tmp_name'];
            $fileName = basename($_FILES[$inputName]['name']);
            $uploadFileDir = UPLOAD_DIR . 'banners/';
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
        }
    }

    // No image provided or method is not POST
    $debugLog .= "No image provided or not a POST request.\n";
    return null;
}

/**
 * Process file uploads.
 *
 * @param string $fileTmpPath Temporary file path.
 * @param string $fileName Original file name.
 * @param string $uploadFileDir Directory to upload the file.
 * @param string &$debugLog Reference to the debug log string.
 * @param string $source Source description for logging.
 * @return string Relative path to the uploaded image.
 */
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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

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
        $relativePath = 'uploads/banners/' . $newFileName;
        $debugLog .= "Image uploaded via {$source}: " . $relativePath . "\n";
        return $relativePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

/**
 * Handle image upload from a URL.
 *
 * @param string $image_url URL of the image.
 * @param string &$debugLog Reference to the debug log string.
 * @return string Relative path to the saved image.
 */
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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'banners/';
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
        $relativePath = 'uploads/banners/' . $newFileName;
        $debugLog .= "Image saved from URL: " . $relativePath . "\n";
        return $relativePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error saving the image from URL']);
        exit();
    }
}

/**
 * Handle image upload from a local server path.
 *
 * @param string $local_image_path Local path to the image.
 * @param string &$debugLog Reference to the debug log string.
 * @return string Relative path to the copied image.
 */
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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'banners/';
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
        $relativePath = 'uploads/banners/' . $newFileName;
        $debugLog .= "Image copied from local path: " . $relativePath . "\n";
        return $relativePath;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error copying the image from local path']);
        exit();
    }
}

// Initialize debug log
$debugLog = "Update Banner Request Received: " . date('Y-m-d H:i:s') . "\n";
$debugLog .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Allow both POST and PUT methods
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Retrieve the banner ID from the query parameter
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Banner ID is required']);
    exit();
}

$id = intval($_GET['id']);
$debugLog .= "Banner ID: " . $id . "\n";

// Handle image upload if the request method is POST
$image_path = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_path = handleImageUpload('image', 'POST', $debugLog);
}

// Handle input data
$inputData = [];

// For PUT requests, parse the raw input
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $inputData);
    $debugLog .= "Parsed PUT Data: " . print_r($inputData, true) . "\n";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests, data is available in $_POST
    $inputData = $_POST;
    $debugLog .= "Parsed POST Data: " . print_r($inputData, true) . "\n";
}

// Retrieve and sanitize input data
$alt_text = isset($inputData['alt_text']) ? trim($inputData['alt_text']) : null;
$active = isset($inputData['active']) ? filter_var($inputData['active'], FILTER_VALIDATE_BOOLEAN) : null;

$debugLog .= "Input Data - Alt Text: {$alt_text}, Active: " . ($active ? 'true' : 'false') . "\n";

// Validate required fields (if necessary)
// In an update, it's common to allow partial updates, so we don't require all fields
if ($alt_text === null && $active === null && $image_path === null) {
    http_response_code(400);
    echo json_encode(['error' => 'No data to update']);
    exit();
}

// If a new image is uploaded, delete the old image
if ($image_path !== null) {
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        if ($banner && $banner['image_path']) {
            $old_image_full_path = __DIR__ . '/../../' . $banner['image_path'];
            if (file_exists($old_image_full_path)) {
                if (unlink($old_image_full_path)) {
                    $debugLog .= "Old image deleted: " . $old_image_full_path . "\n";
                } else {
                    $debugLog .= "Failed to delete old image: " . $old_image_full_path . "\n";
                }
            }
        }
    } catch (PDOException $e) {
        // Log the error but don't halt the execution
        $debugLog .= "Error fetching old image path: " . $e->getMessage() . "\n";
    }
}

// Build the SQL UPDATE query dynamically
$fields = [];
$values = [];

if ($image_path !== null) {
    $fields[] = 'image_path = ?';
    $values[] = $image_path;
}

if ($alt_text !== null) {
    $fields[] = 'alt_text = ?';
    $values[] = $alt_text;
}

if ($active !== null) {
    $fields[] = 'active = ?';
    $values[] = $active;
}

if (empty($fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid data provided for update']);
    exit();
}

$values[] = $id; // For WHERE clause

$sql = "UPDATE banners SET " . implode(', ', $fields) . " WHERE id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    if ($stmt->rowCount() > 0) {
        $debugLog .= "Banner updated successfully.\n";
        echo json_encode([
            'message' => 'Banner updated successfully',
            'image_path' => $image_path,
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Banner not found or no changes were made']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An internal error occurred',
        'details' => $e->getMessage(),
    ]);
    $debugLog .= "Database Error: " . $e->getMessage() . "\n";
}

// Write debug log to file
file_put_contents('debug.log', $debugLog . "\n", FILE_APPEND);
?>
