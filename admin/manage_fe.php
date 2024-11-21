<?php
// backend/admin/manage_fe.php

// Header CORS untuk mengizinkan permintaan dari frontend
header("Access-Control-Allow-Origin: http://localhost:3000"); // Ganti dengan origin frontend Anda
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Menangani preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
session_start();

// Implementasikan otentikasi jika diperlukan
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Akses tidak diizinkan.', 'data' => null]);
    exit;
}
*/

// Aktifkan error reporting untuk pengembangan (nonaktifkan di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan skrip koneksi database
require_once 'includes/db_connect.php';

// Fungsi untuk menyaring input
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Inisialisasi array respons
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Ambil parameter aksi dari POST
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    // === Manajemen Kategori ===
    case 'add_category':
        $name = cleanInput($_POST['category_name']);
        $description = cleanInput($_POST['category_description']);

        if (empty($name)) {
            $response['message'] = "Nama kategori tidak boleh kosong.";
        } else {
            // Cek apakah kategori sudah ada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                $response['message'] = "Kategori dengan nama ini sudah ada.";
            } else {
                // Tambahkan kategori baru
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                if ($stmt->execute([$name, $description])) {
                    $response['success'] = true;
                    $response['message'] = "Kategori berhasil ditambahkan.";
                } else {
                    $response['message'] = "Gagal menambahkan kategori.";
                }
            }
        }
        break;

    case 'get_categories':
        try {
            $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY id DESC");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['data'] = $categories;
        } catch (PDOException $e) {
            $response['message'] = "Error fetching categories: " . $e->getMessage();
        }
        break;

    case 'delete_category':
        $id = intval($_POST['category_id']);

        // Cek apakah kategori ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = "Kategori tidak ditemukan.";
        } else {
            // Hapus kategori
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $response['success'] = true;
                $response['message'] = "Kategori berhasil dihapus.";
            } else {
                $response['message'] = "Gagal menghapus kategori.";
            }
        }
        break;

    case 'edit_category':
        $id = intval($_POST['edit_category_id']);
        $name = cleanInput($_POST['edit_category_name']);
        $description = cleanInput($_POST['edit_category_description']);

        if (empty($name)) {
            $response['message'] = "Nama kategori tidak boleh kosong.";
        } else {
            // Cek apakah ada kategori lain dengan nama yang sama
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetchColumn() > 0) {
                $response['message'] = "Kategori lain dengan nama ini sudah ada.";
            } else {
                // Update kategori
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $id])) {
                    $response['success'] = true;
                    $response['message'] = "Kategori berhasil diperbarui.";
                } else {
                    $response['message'] = "Gagal memperbarui kategori.";
                }
            }
        }
        break;

    // === Manajemen Banner ===
    case 'add_banner':
        // Periksa apakah file diupload
        if (!isset($_FILES['banner_image']) || $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = "Gambar banner diperlukan.";
        } else {
            // Proses upload gambar
            $fileTmpPath = $_FILES['banner_image']['tmp_name'];
            $fileName = basename($_FILES['banner_image']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validasi ekstensi gambar
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $response['message'] = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
            } else {
                // Buat nama file unik
                $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

                // Tentukan direktori upload
                $uploadDir = 'uploads/banners/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Simpan path relatif untuk database
                    $imagePath = 'uploads/banners/' . $newFileName;
                } else {
                    $response['message'] = "Terjadi kesalahan saat mengupload gambar banner.";
                }
            }
        }

        if (!isset($response['message'])) {
            $altText = cleanInput($_POST['banner_alt_text']);
            $active = isset($_POST['banner_active']) ? 1 : 0;

            // Simpan banner ke database
            try {
                $stmt = $pdo->prepare("INSERT INTO banners (image_path, alt_text, active) VALUES (?, ?, ?)");
                if ($stmt->execute([$imagePath, $altText, $active])) {
                    $response['success'] = true;
                    $response['message'] = "Banner berhasil ditambahkan.";
                } else {
                    $response['message'] = "Gagal menambahkan banner.";
                }
            } catch (PDOException $e) {
                $response['message'] = "Error adding banner: " . $e->getMessage();
            }
        }
        break;

    case 'get_banners':
        try {
            $stmt = $pdo->prepare("SELECT * FROM banners ORDER BY id DESC");
            $stmt->execute();
            $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['data'] = $banners;
        } catch (PDOException $e) {
            $response['message'] = "Error fetching banners: " . $e->getMessage();
        }
        break;

    case 'delete_banner':
        $id = intval($_POST['banner_id']);

        // Ambil banner untuk mendapatkan path gambar
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$banner) {
            $response['message'] = "Banner tidak ditemukan.";
        } else {
            // Hapus file gambar
            $filePath = $banner['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Hapus banner dari database
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            if ($stmt->execute([$id])) {
                $response['success'] = true;
                $response['message'] = "Banner berhasil dihapus.";
            } else {
                $response['message'] = "Gagal menghapus banner.";
            }
        }
        break;

    case 'edit_banner':
        $id = intval($_POST['edit_banner_id']);
        $altText = cleanInput($_POST['edit_banner_alt_text']);
        $active = isset($_POST['edit_banner_active']) ? 1 : 0;

        // Ambil banner yang akan diedit
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$banner) {
            $response['message'] = "Banner tidak ditemukan.";
        } else {
            // Periksa apakah ada file gambar baru diupload
            if (isset($_FILES['edit_banner_image']) && $_FILES['edit_banner_image']['error'] === UPLOAD_ERR_OK) {
                // Proses upload gambar baru
                $fileTmpPath = $_FILES['edit_banner_image']['tmp_name'];
                $fileName = basename($_FILES['edit_banner_image']['name']);
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Validasi ekstensi gambar
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $response['message'] = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
                } else {
                    // Buat nama file unik
                    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

                    // Tentukan direktori upload
                    $uploadDir = 'uploads/banners/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Simpan path relatif untuk database
                        $imagePath = 'uploads/banners/' . $newFileName;

                        // Hapus file gambar lama
                        if (file_exists($banner['image_path'])) {
                            unlink($banner['image_path']);
                        }
                    } else {
                        $response['message'] = "Terjadi kesalahan saat mengupload gambar banner baru.";
                    }
                }
            } else {
                // Gunakan path gambar lama jika tidak ada gambar baru diupload
                $imagePath = $banner['image_path'];
            }

            if (!isset($response['message'])) {
                // Update banner di database
                try {
                    $stmt = $pdo->prepare("UPDATE banners SET image_path = ?, alt_text = ?, active = ? WHERE id = ?");
                    if ($stmt->execute([$imagePath, $altText, $active, $id])) {
                        $response['success'] = true;
                        $response['message'] = "Banner berhasil diperbarui.";
                    } else {
                        $response['message'] = "Gagal memperbarui banner.";
                    }
                } catch (PDOException $e) {
                    $response['message'] = "Error updating banner: " . $e->getMessage();
                }
            }
        }
        break;

    // === Manajemen Produk ===
    case 'add_product':
        // Periksa apakah file diupload
        if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = "Gambar produk diperlukan.";
        } else {
            // Proses upload gambar
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = basename($_FILES['product_image']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validasi ekstensi gambar
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $response['message'] = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
            } else {
                // Buat nama file unik
                $newFileName = uniqid('product_', true) . '.' . $fileExtension;

                // Tentukan direktori upload
                $uploadDir = 'uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Simpan path relatif untuk database
                    $imagePath = 'uploads/products/' . $newFileName;
                } else {
                    $response['message'] = "Terjadi kesalahan saat mengupload gambar produk.";
                }
            }
        }

        if (!isset($response['message'])) {
            $name = cleanInput($_POST['product_name']);
            $description = cleanInput($_POST['product_description']);
            $price = floatval(str_replace(',', '', $_POST['product_price']));
            $category_id = intval($_POST['product_category']);

            if (empty($name) || empty($price) || empty($category_id)) {
                $response['message'] = "Nama produk, harga, dan kategori diperlukan.";
            } else {
                // Simpan produk ke database
                try {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_path, category_id) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$name, $description, $price, $imagePath, $category_id])) {
                        $response['success'] = true;
                        $response['message'] = "Produk berhasil ditambahkan.";
                    } else {
                        $response['message'] = "Gagal menambahkan produk.";
                    }
                } catch (PDOException $e) {
                    $response['message'] = "Error adding product: " . $e->getMessage();
                }
            }
        }
        break;

    case 'get_products':
        try {
            $stmt = $pdo->prepare("SELECT products.*, categories.name AS category_name FROM products JOIN categories ON products.category_id = categories.id ORDER BY products.id DESC");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['data'] = $products;
        } catch (PDOException $e) {
            $response['message'] = "Error fetching products: " . $e->getMessage();
        }
        break;

    case 'delete_product':
        $id = intval($_POST['product_id']);

        // Ambil produk untuk mendapatkan path gambar
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $response['message'] = "Produk tidak ditemukan.";
        } else {
            // Hapus file gambar
            $filePath = $product['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Hapus produk dari database
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$id])) {
                $response['success'] = true;
                $response['message'] = "Produk berhasil dihapus.";
            } else {
                $response['message'] = "Gagal menghapus produk.";
            }
        }
        break;

    case 'edit_product':
        $id = intval($_POST['edit_product_id']);
        $name = cleanInput($_POST['edit_product_name']);
        $description = cleanInput($_POST['edit_product_description']);
        $price = floatval(str_replace(',', '', $_POST['edit_product_price']));
        $category_id = intval($_POST['edit_product_category']);

        // Ambil produk yang akan diedit
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $response['message'] = "Produk tidak ditemukan.";
        } else {
            // Periksa apakah ada file gambar baru diupload
            if (isset($_FILES['edit_product_image']) && $_FILES['edit_product_image']['error'] === UPLOAD_ERR_OK) {
                // Proses upload gambar baru
                $fileTmpPath = $_FILES['edit_product_image']['tmp_name'];
                $fileName = basename($_FILES['edit_product_image']['name']);
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Validasi ekstensi gambar
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $response['message'] = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
                } else {
                    // Buat nama file unik
                    $newFileName = uniqid('product_', true) . '.' . $fileExtension;

                    // Tentukan direktori upload
                    $uploadDir = 'uploads/products/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Simpan path relatif untuk database
                        $imagePath = 'uploads/products/' . $newFileName;

                        // Hapus file gambar lama
                        if (file_exists($product['image_path'])) {
                            unlink($product['image_path']);
                        }
                    } else {
                        $response['message'] = "Terjadi kesalahan saat mengupload gambar produk baru.";
                    }
                }
            } else {
                // Gunakan path gambar lama jika tidak ada gambar baru diupload
                $imagePath = $product['image_path'];
            }

            if (!isset($response['message'])) {
                // Update produk di database
                try {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_path = ?, category_id = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $price, $imagePath, $category_id, $id])) {
                        $response['success'] = true;
                        $response['message'] = "Produk berhasil diperbarui.";
                    } else {
                        $response['message'] = "Gagal memperbarui produk.";
                    }
                } catch (PDOException $e) {
                    $response['message'] = "Error updating product: " . $e->getMessage();
                }
            }
        }
        break;

    default:
        $response['message'] = "Invalid action.";
        break;
}

// Kembalikan respons sebagai JSON
echo json_encode($response);
?>
