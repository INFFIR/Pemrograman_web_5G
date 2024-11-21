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

// Tambahkan logging untuk debugging
file_put_contents('debug.log', "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents('debug.log', "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', "FILES Data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

require_once '../../includes/db_connect.php';

// Definisikan UPLOAD_DIR jika belum didefinisikan
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
}

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Fungsi untuk memvalidasi dan menyimpan gambar
function handleImageUpload($fileTmpPath, $fileName, $uploadFileDir) {
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

    // Pastikan direktori unggahan ada
    if (!is_dir($uploadFileDir)) {
        if (!mkdir($uploadFileDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit();
        }
    }

    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // Simpan path relatif untuk database
        return 'uploads/products/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

// Inisialisasi variabel image_path
$image_path = null;

// Cek apakah ada gambar yang di-upload via file
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Proses unggah gambar via file
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = basename($_FILES['image']['name']);
    $uploadFileDir = UPLOAD_DIR . 'products/';
    $image_path = handleImageUpload($fileTmpPath, $fileName, $uploadFileDir);
    file_put_contents('debug.log', "Image uploaded via file: " . $image_path . "\n", FILE_APPEND);
} 
// Jika tidak, cek apakah ada image_url
elseif (isset($_POST['image_url']) && filter_var($_POST['image_url'], FILTER_VALIDATE_URL)) {
    $image_url = $_POST['image_url'];
    file_put_contents('debug.log', "Processing image_url: " . $image_url . "\n", FILE_APPEND);
    
    // Dapatkan konten gambar dari URL
    $imageData = @file_get_contents($image_url);
    if ($imageData === FALSE) {
        http_response_code(400);
        echo json_encode(['error' => 'Unable to fetch image from URL']);
        exit();
    } else {
        file_put_contents('debug.log', "Image fetched successfully from URL.\n", FILE_APPEND);
    }

    // Tentukan nama file dari URL
    $fileName = basename(parse_url($image_url, PHP_URL_PATH));
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validasi ekstensi gambar
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format from URL']);
        exit();
    }

    // Buat nama unik untuk file
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'products/';
    $dest_path = $uploadFileDir . $newFileName;

    // Pastikan direktori unggahan ada
    if (!is_dir($uploadFileDir)) {
        if (!mkdir($uploadFileDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit();
        }
    }

    // Simpan gambar dari URL ke direktori unggahan
    if (file_put_contents($dest_path, $imageData) !== FALSE) {
        $image_path = 'uploads/products/' . $newFileName;
        file_put_contents('debug.log', "Image saved from URL: " . $image_path . "\n", FILE_APPEND);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error saving the image from URL']);
        exit();
    }
} 
// Jika tidak, cek apakah ada image_path (path direktori lokal di server)
elseif (isset($_POST['image_path']) && !empty($_POST['image_path'])) {
    $local_image_path = $_POST['image_path'];
    file_put_contents('debug.log', "Processing image_path: " . $local_image_path . "\n", FILE_APPEND);

    // Pastikan file ada
    if (!file_exists($local_image_path)) {
        http_response_code(400);
        echo json_encode(['error' => 'Local image file does not exist']);
        exit();
    }

    // Dapatkan ekstensi file
    $fileExtension = strtolower(pathinfo($local_image_path, PATHINFO_EXTENSION));

    // Validasi ekstensi gambar
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format from local path']);
        exit();
    }

    // Buat nama unik untuk file
    $newFileName = uniqid('product_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'products/';
    $dest_path = $uploadFileDir . $newFileName;

    // Pastikan direktori unggahan ada
    if (!is_dir($uploadFileDir)) {
        if (!mkdir($uploadFileDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit();
        }
    }

    // Salin gambar dari path lokal ke direktori unggahan
    if (copy($local_image_path, $dest_path)) {
        $image_path = 'uploads/products/' . $newFileName;
        file_put_contents('debug.log', "Image copied from local path: " . $image_path . "\n", FILE_APPEND);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error copying the image from local path']);
        exit();
    }
} else {
    // Jika tidak ada gambar yang di-upload, set image_path ke null atau default
    $image_path = null; // Atau Anda bisa menetapkan path default di sini
    file_put_contents('debug.log', "No image provided.\n", FILE_APPEND);
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
    file_put_contents('debug.log', "Product inserted with ID: " . $id . "\n", FILE_APPEND);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
    file_put_contents('debug.log', "Database Error: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
