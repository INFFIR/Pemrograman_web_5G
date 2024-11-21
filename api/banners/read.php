<?php
// backend/api/banners/read.php
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
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Basis URL untuk gambar
$base_url = "http://localhost:8000/"; // Ganti dengan domain dan port backend Anda

try {
    if (isset($_GET['id'])) {
        // Ambil banner berdasarkan ID
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($banner) {
            if (isset($banner['image_path'])) {
                $banner['image_url'] = $base_url . $banner['image_path'];
            }
            echo json_encode($banner);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Banner not found']);
        }
    } else {
        // Ambil semua banner
        $stmt = $pdo->prepare("SELECT * FROM banners");
        $stmt->execute();
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tambahkan URL lengkap untuk gambar
        foreach ($banners as &$banner) {
            if (isset($banner['image_path'])) {
                $banner['image_url'] = $base_url . $banner['image_path'];
            }
        }

        echo json_encode($banners);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
