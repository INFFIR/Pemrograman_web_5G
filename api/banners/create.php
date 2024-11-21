<?php
// backend/api/banners/create.php
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

// Cek apakah request menggunakan multipart/form-data
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Image is required']);
    exit();
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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

    // Tentukan direktori unggahan
    $uploadFileDir = UPLOAD_DIR . 'banners/';
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
    }

    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // Simpan path relatif untuk database
        $image_path = 'uploads/banners/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

// Ambil data form
$alt_text = isset($_POST['alt_text']) ? trim($_POST['alt_text']) : '';
$active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

// Insert ke database
try {
    $stmt = $pdo->prepare("INSERT INTO banners (image_path, alt_text, active) VALUES (?, ?, ?)");
    $stmt->execute([$image_path, $alt_text, $active]);
    $id = $pdo->lastInsertId();
    echo json_encode(['message' => 'Banner created', 'id' => $id, 'image_path' => $image_path]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
