<?php
// backend/api/products/create.php
header('Content-Type: application/json');

// Atur CORS
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); // Ganti dengan domain frontend Anda
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

// Cek apakah request menggunakan multipart/form-data
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $image_path = null; // Atau set path default
} else {
    // Proses unggah gambar
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = basename($_FILES['image']['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validasi ekstensi gambar
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format']);
        exit();
    }

    // Buat nama unik untuk file
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;

    // Tentukan direktori unggahan
    $uploadFileDir = UPLOAD_DIR . 'products/';
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
    }

    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // Simpan path relatif untuk database
        $image_path = 'uploads/products/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

// Ambil data form
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$price = isset($_POST['price']) ? floatval(str_replace(',', '', $_POST['price'])) : null;
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

// Validasi input
if (!$name || !$price) {
    http_response_code(400);
    echo json_encode(['error' => 'Product name and price are required']);
    exit();
}

// Insert ke database
try {
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_path, category_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $image_path, $category_id]);
    $id = $pdo->lastInsertId();
    echo json_encode(['message' => 'Product created', 'id' => $id, 'image_path' => $image_path]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
