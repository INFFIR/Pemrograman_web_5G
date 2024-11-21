<?php
// backend/api/banners/create.php
header('Content-Type: application/json');

// Atur CORS
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); // Ganti dengan domain frontend Anda
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Tambahkan pengecekan apakah REQUEST_METHOD ada
if (!isset($_SERVER['REQUEST_METHOD'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Handle preflight request
    exit(0);
}

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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

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
        return 'uploads/banners/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error uploading the image']);
        exit();
    }
}

// Fungsi untuk mengambil gambar dari URL menggunakan cURL
function getImageFromUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Set timeout untuk mencegah skrip menunggu terlalu lama
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($data === FALSE || $http_code != 200) {
        curl_close($ch);
        return FALSE;
    }
    curl_close($ch);
    return $data;
}

// Inisialisasi variabel image_path
$image_path = null;

// Cek apakah ada gambar yang di-upload via file
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Proses unggah gambar via file
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = basename($_FILES['image']['name']);
    $uploadFileDir = UPLOAD_DIR . 'banners/';
    $image_path = handleImageUpload($fileTmpPath, $fileName, $uploadFileDir);
} 
// Jika tidak, cek apakah ada image_url
elseif (isset($_POST['image_url']) && filter_var($_POST['image_url'], FILTER_VALIDATE_URL)) {
    $image_url = $_POST['image_url'];
    
    // Dapatkan konten gambar dari URL menggunakan cURL
    $imageData = getImageFromUrl($image_url);
    if ($imageData === FALSE) {
        http_response_code(400);
        echo json_encode(['error' => 'Unable to fetch image from URL']);
        exit();
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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'banners/';
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
        $image_path = 'uploads/banners/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error saving the image from URL']);
        exit();
    }
} 
// Jika tidak, cek apakah ada image_path (path direktori lokal di server)
elseif (isset($_POST['image_path']) && !empty($_POST['image_path'])) {
    $local_image_path = $_POST['image_path'];

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
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
    $uploadFileDir = UPLOAD_DIR . 'banners/';
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
        $image_path = 'uploads/banners/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error copying the image from local path']);
        exit();
    }
} else {
    // Jika tidak ada gambar yang di-upload, set image_path ke null atau default
    $image_path = null; // Atau Anda bisa menetapkan path default di sini
}

// Ambil data form
$alt_text = isset($_POST['alt_text']) ? trim($_POST['alt_text']) : '';
$active = isset($_POST['active']) ? (bool)$_POST['active'] : true;

// Validasi input (opsional)
if ($image_path === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Image is required']);
    exit();
}

// Insert ke database
try {
    $stmt = $pdo->prepare("INSERT INTO banners (image_path, alt_text, active) VALUES (?, ?, ?)");
    $stmt->execute([$image_path, $alt_text, $active]);
    $id = $pdo->lastInsertId();
    echo json_encode(['message' => 'Banner created', 'id' => $id, 'image_path' => $image_path]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
