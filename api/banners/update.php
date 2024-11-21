<?php
// backend/api/banners/update.php
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Gunakan POST untuk update karena browser tidak mendukung PUT dengan multipart/form-data
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Ambil ID dari query parameter
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Banner ID is required']);
    exit();
}

$id = intval($_GET['id']);

// Cek apakah ada gambar yang diunggah
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $image_path = null; // Tidak ada perubahan pada gambar
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

        // Hapus gambar lama dari server jika ada
        $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        if ($banner && $banner['image_path']) {
            $old_image_full_path = '../../' . $banner['image_path'];
            if (file_exists($old_image_full_path)) {
                unlink($old_image_full_path);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

// Ambil data form
$alt_text = isset($_POST['alt_text']) ? trim($_POST['alt_text']) : null;
$active = isset($_POST['active']) ? (bool)$_POST['active'] : null;

// Bangun query update
$fields = [];
$values = [];

if ($image_path) {
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
    echo json_encode(['error' => 'No data to update']);
    exit();
}

$values[] = $id; // Untuk WHERE

$sql = "UPDATE banners SET " . implode(', ', $fields) . " WHERE id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Banner updated', 'image_path' => $image_path]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Banner not found or no change']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
