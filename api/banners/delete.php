<?php
// backend/api/banners/delete.php
header('Content-Type: application/json');

// Atur CORS
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); // Ganti dengan domain frontend Anda
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight request
    exit();
}

require_once '../../includes/db_connect.php';

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

// Delete banner
try {
    // Hapus gambar dari server jika ada
    $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if ($banner && $banner['image_path']) {
        $image_full_path = '../../' . $banner['image_path'];
        if (file_exists($image_full_path)) {
            unlink($image_full_path);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Banner deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Banner not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
