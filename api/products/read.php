<?php
// backend/api/products/read.php
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
        // Ambil produk berdasarkan ID
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id WHERE products.id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            if (isset($product['image_path'])) {
                $product['image_url'] = $base_url . $product['image_path'];
            }
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    } else {
        // Ambil semua produk
        $stmt = $pdo->prepare("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tambahkan URL lengkap untuk gambar
        foreach ($products as &$product) {
            if (isset($product['image_path'])) {
                $product['image_url'] = $base_url . $product['image_path'];
            }
        }

        echo json_encode($products);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
