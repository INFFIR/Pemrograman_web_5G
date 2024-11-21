<?php
// backend/admin/manage.php

// Aktifkan error reporting untuk debugging (matikan di production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inklusi koneksi database
require_once '../includes/db_connect.php';

// Fungsi untuk membersihkan input
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// === Bagian Manajemen Kategori ===

// Handle penambahan kategori baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = cleanInput($_POST['category_name']);
    $description = cleanInput($_POST['category_description']);

    if (empty($name)) {
        $category_error = "Nama kategori tidak boleh kosong.";
    } else {
        // Cek apakah kategori sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $category_error = "Kategori dengan nama tersebut sudah ada.";
        } else {
            // Insert kategori baru
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $category_success = "Kategori berhasil ditambahkan.";
            } else {
                $category_error = "Gagal menambahkan kategori.";
            }
        }
    }
}

// Handle penghapusan kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = intval($_POST['category_id']);

    // Cek apakah kategori ada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        $category_error = "Kategori tidak ditemukan.";
    } else {
        // Hapus kategori
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $category_success = "Kategori berhasil dihapus.";
        } else {
            $category_error = "Gagal menghapus kategori.";
        }
    }
}

// Handle pengeditan kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = intval($_POST['edit_category_id']);
    $name = cleanInput($_POST['edit_category_name']);
    $description = cleanInput($_POST['edit_category_description']);

    if (empty($name)) {
        $category_error = "Nama kategori tidak boleh kosong.";
    } else {
        // Cek apakah kategori dengan nama tersebut sudah ada (selain yang sedang diedit)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $category_error = "Kategori dengan nama tersebut sudah ada.";
        } else {
            // Update kategori
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $id])) {
                $category_success = "Kategori berhasil diperbarui.";
            } else {
                $category_error = "Gagal memperbarui kategori.";
            }
        }
    }
}

// Ambil semua kategori dari database
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY id DESC");
$stmt->execute();
$categories = $stmt->fetchAll();

// === Bagian Manajemen Banner ===

// Handle penambahan banner baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_banner'])) {
    // Cek apakah request menggunakan multipart/form-data dan memiliki file gambar
    if (!isset($_FILES['banner_image']) || $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
        $banner_error = "Gambar banner diperlukan.";
    } else {
        // Proses unggah gambar
        $fileTmpPath = $_FILES['banner_image']['tmp_name'];
        $fileName = basename($_FILES['banner_image']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validasi ekstensi gambar
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $banner_error = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diizinkan.";
        } else {
            // Buat nama unik untuk file
            $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

            // Tentukan direktori unggahan
            $uploadFileDir = '../uploads/banners/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Simpan path relatif untuk database
                $image_path = 'uploads/banners/' . $newFileName;
            } else {
                $banner_error = "Terjadi kesalahan saat mengunggah gambar banner.";
            }
        }
    }

    // Jika gambar berhasil diunggah
    if (!isset($banner_error)) {
        $alt_text = cleanInput($_POST['banner_alt_text']);
        $active = isset($_POST['banner_active']) ? 1 : 0;

        // Insert banner baru
        try {
            $stmt = $pdo->prepare("INSERT INTO banners (image_path, alt_text, active) VALUES (?, ?, ?)");
            if ($stmt->execute([$image_path, $alt_text, $active])) {
                $banner_success = "Banner berhasil ditambahkan.";
            } else {
                $banner_error = "Gagal menambahkan banner.";
            }
        } catch (PDOException $e) {
            $banner_error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Handle penghapusan banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_banner'])) {
    $id = intval($_POST['banner_id']);

    // Cek apakah banner ada
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();

    if (!$banner) {
        $banner_error = "Banner tidak ditemukan.";
    } else {
        // Hapus file gambar dari server
        $filePath = '../' . $banner['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Hapus banner dari database
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        if ($stmt->execute([$id])) {
            $banner_success = "Banner berhasil dihapus.";
        } else {
            $banner_error = "Gagal menghapus banner.";
        }
    }
}

// Handle pengeditan banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_banner'])) {
    $id = intval($_POST['edit_banner_id']);
    $alt_text = cleanInput($_POST['edit_banner_alt_text']);
    $active = isset($_POST['edit_banner_active']) ? 1 : 0;

    // Cek apakah banner ada
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();

    if (!$banner) {
        $banner_error = "Banner tidak ditemukan.";
    } else {
        // Jika ada gambar baru yang diunggah
        if (isset($_FILES['edit_banner_image']) && $_FILES['edit_banner_image']['error'] === UPLOAD_ERR_OK) {
            // Proses unggah gambar baru
            $fileTmpPath = $_FILES['edit_banner_image']['tmp_name'];
            $fileName = basename($_FILES['edit_banner_image']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validasi ekstensi gambar
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $banner_error = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diizinkan.";
            } else {
                // Buat nama unik untuk file
                $newFileName = uniqid('banner_', true) . '.' . $fileExtension;

                // Tentukan direktori unggahan
                $uploadFileDir = '../uploads/banners/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Simpan path relatif untuk database
                    $image_path = 'uploads/banners/' . $newFileName;

                    // Hapus file gambar lama dari server
                    $old_filePath = '../' . $banner['image_path'];
                    if (file_exists($old_filePath)) {
                        unlink($old_filePath);
                    }
                } else {
                    $banner_error = "Terjadi kesalahan saat mengunggah gambar banner.";
                }
            }
        } else {
            // Jika tidak ada gambar baru, gunakan gambar lama
            $image_path = $banner['image_path'];
        }

        // Jika tidak ada error
        if (!isset($banner_error)) {
            // Update banner
            $stmt = $pdo->prepare("UPDATE banners SET image_path = ?, alt_text = ?, active = ? WHERE id = ?");
            if ($stmt->execute([$image_path, $alt_text, $active, $id])) {
                $banner_success = "Banner berhasil diperbarui.";
            } else {
                $banner_error = "Gagal memperbarui banner.";
            }
        }
    }
}

// Ambil semua banner dari database
$stmt = $pdo->prepare("SELECT * FROM banners ORDER BY id DESC");
$stmt->execute();
$banners = $stmt->fetchAll();

// === Bagian Manajemen Produk ===

// Handle penambahan produk baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Cek apakah request menggunakan multipart/form-data dan memiliki file gambar
    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        $product_error = "Gambar produk diperlukan.";
    } else {
        // Proses unggah gambar
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = basename($_FILES['product_image']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validasi ekstensi gambar
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $product_error = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diizinkan.";
        } else {
            // Buat nama unik untuk file
            $newFileName = uniqid('product_', true) . '.' . $fileExtension;

            // Tentukan direktori unggahan
            $uploadFileDir = '../uploads/products/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Simpan path relatif untuk database
                $image_path = 'uploads/products/' . $newFileName;
            } else {
                $product_error = "Terjadi kesalahan saat mengunggah gambar produk.";
            }
        }
    }

    // Jika gambar berhasil diunggah
    if (!isset($product_error)) {
        $name = cleanInput($_POST['product_name']);
        $description = cleanInput($_POST['product_description']);
        $price = floatval(str_replace(',', '', $_POST['product_price']));
        $category_id = intval($_POST['product_category']);

        if (empty($name) || empty($price) || empty($category_id)) {
            $product_error = "Nama produk, harga, dan kategori harus diisi.";
        } else {
            // Insert produk baru
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_path, category_id) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $description, $price, $image_path, $category_id])) {
                    $product_success = "Produk berhasil ditambahkan.";
                } else {
                    $product_error = "Gagal menambahkan produk.";
                }
            } catch (PDOException $e) {
                $product_error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

// Handle penghapusan produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = intval($_POST['product_id']);

    // Cek apakah produk ada
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        $product_error = "Produk tidak ditemukan.";
    } else {
        // Hapus file gambar dari server
        $filePath = '../' . $product['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Hapus produk dari database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            $product_success = "Produk berhasil dihapus.";
        } else {
            $product_error = "Gagal menghapus produk.";
        }
    }
}

// Handle pengeditan produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = intval($_POST['edit_product_id']);
    $name = cleanInput($_POST['edit_product_name']);
    $description = cleanInput($_POST['edit_product_description']);
    $price = floatval(str_replace(',', '', $_POST['edit_product_price']));
    $category_id = intval($_POST['edit_product_category']);

    // Cek apakah produk ada
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        $product_error = "Produk tidak ditemukan.";
    } else {
        // Jika ada gambar baru yang diunggah
        if (isset($_FILES['edit_product_image']) && $_FILES['edit_product_image']['error'] === UPLOAD_ERR_OK) {
            // Proses unggah gambar baru
            $fileTmpPath = $_FILES['edit_product_image']['tmp_name'];
            $fileName = basename($_FILES['edit_product_image']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validasi ekstensi gambar
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $product_error = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diizinkan.";
            } else {
                // Buat nama unik untuk file
                $newFileName = uniqid('product_', true) . '.' . $fileExtension;

                // Tentukan direktori unggahan
                $uploadFileDir = '../uploads/products/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Simpan path relatif untuk database
                    $image_path = 'uploads/products/' . $newFileName;

                    // Hapus file gambar lama dari server
                    $old_filePath = '../' . $product['image_path'];
                    if (file_exists($old_filePath)) {
                        unlink($old_filePath);
                    }
                } else {
                    $product_error = "Terjadi kesalahan saat mengunggah gambar produk.";
                }
            }
        } else {
            // Jika tidak ada gambar baru, gunakan gambar lama
            $image_path = $product['image_path'];
        }

        // Jika tidak ada error
        if (!isset($product_error)) {
            // Update produk
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_path = ?, category_id = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $price, $image_path, $category_id, $id])) {
                $product_success = "Produk berhasil diperbarui.";
            } else {
                $product_error = "Gagal memperbarui produk.";
            }
        }
    }
}

// Ambil semua produk dari database
$stmt = $pdo->prepare("SELECT products.*, categories.name AS category_name FROM products JOIN categories ON products.category_id = categories.id ORDER BY products.id DESC");
$stmt->execute();
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manage - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css"> <!-- Pastikan Anda memiliki CSS untuk admin -->
    <style>
        /* CSS Sederhana untuk halaman admin */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        section {
            margin-bottom: 50px;
        }

        /* Form Styles */
        form {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"], textarea, select, input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 10px 15px;
            background-color: #28a745;
            border: none;
            color: #fff;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #218838;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .btn-edit {
            background-color: #007bff;
        }

        .btn-edit:hover {
            background-color: #0069d9;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Modal Styles */
        #editCategoryModal, #editBannerModal, #editProductModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        #editCategoryModal .modal-content, #editBannerModal .modal-content, #editProductModal .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 5px;
            width: 500px;
            position: relative;
        }

        #editCategoryModal .close, #editBannerModal .close, #editProductModal .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 20px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        #editCategoryModal .close:hover, #editBannerModal .close:hover, #editProductModal .close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel - Manage</h1>

        <!-- === Manajemen Kategori === -->
        <section id="manage-categories">
            <h2>Manage Categories</h2>

            <?php if (isset($category_success)): ?>
                <div class="message success"><?php echo $category_success; ?></div>
            <?php endif; ?>

            <?php if (isset($category_error)): ?>
                <div class="message error"><?php echo $category_error; ?></div>
            <?php endif; ?>

            <!-- Form Tambah Kategori -->
            <form method="POST" action="manage.php">
                <input type="hidden" name="add_category" value="1">
                <label for="category_name">Category Name:</label>
                <input type="text" id="category_name" name="category_name" required>

                <label for="category_description">Description:</label>
                <textarea id="category_description" name="category_description" rows="3"></textarea>

                <button type="submit">Add Category</button>
            </form>

            <!-- Daftar Kategori -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <button class="btn-edit" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo addslashes(htmlspecialchars($category['name'])); ?>', '<?php echo addslashes(htmlspecialchars($category['description'])); ?>')">Edit</button>
                                    
                                    <!-- Tombol Hapus -->
                                    <form method="POST" action="manage.php" style="display:inline;">
                                        <input type="hidden" name="delete_category" value="1">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- === Manajemen Banner === -->
        <section id="manage-banners">
            <h2>Manage Banners</h2>

            <?php if (isset($banner_success)): ?>
                <div class="message success"><?php echo $banner_success; ?></div>
            <?php endif; ?>

            <?php if (isset($banner_error)): ?>
                <div class="message error"><?php echo $banner_error; ?></div>
            <?php endif; ?>

            <!-- Form Tambah Banner -->
            <form method="POST" action="manage.php" enctype="multipart/form-data">
                <input type="hidden" name="add_banner" value="1">
                <label for="banner_image">Banner Image:</label>
                <input type="file" id="banner_image" name="banner_image" accept="image/*" required>

                <label for="banner_alt_text">Alt Text:</label>
                <input type="text" id="banner_alt_text" name="banner_alt_text">

                <label for="banner_active">Active:</label>
                <input type="checkbox" id="banner_active" name="banner_active" checked>

                <button type="submit">Add Banner</button>
            </form>

            <!-- Daftar Banner -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Alt Text</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($banners) > 0): ?>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><?php echo $banner['id']; ?></td>
                                <td><img src="../<?php echo $banner['image_path']; ?>" alt="<?php echo htmlspecialchars($banner['alt_text']); ?>" width="100"></td>
                                <td><?php echo htmlspecialchars($banner['alt_text']); ?></td>
                                <td><?php echo $banner['active'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <button class="btn-edit" onclick="editBanner(<?php echo $banner['id']; ?>, '<?php echo addslashes(htmlspecialchars($banner['alt_text'])); ?>', '<?php echo $banner['active'] ? '1' : '0'; ?>')">Edit</button>
                                    
                                    <!-- Tombol Hapus -->
                                    <form method="POST" action="manage.php" style="display:inline;">
                                        <input type="hidden" name="delete_banner" value="1">
                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this banner?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No banners found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- === Manajemen Produk === -->
        <section id="manage-products">
            <h2>Manage Products</h2>

            <?php if (isset($product_success)): ?>
                <div class="message success"><?php echo $product_success; ?></div>
            <?php endif; ?>

            <?php if (isset($product_error)): ?>
                <div class="message error"><?php echo $product_error; ?></div>
            <?php endif; ?>

            <!-- Form Tambah Produk -->
            <form method="POST" action="manage.php" enctype="multipart/form-data">
                <input type="hidden" name="add_product" value="1">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" required>

                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" rows="3"></textarea>

                <label for="product_price">Price (Rp.):</label>
                <input type="text" id="product_price" name="product_price" required>

                <label for="product_category">Category:</label>
                <select id="product_category" name="product_category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="product_image">Product Image:</label>
                <input type="file" id="product_image" name="product_image" accept="image/*">

                <button type="submit">Add Product</button>
            </form>

            <!-- Daftar Produk -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (Rp.)</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><img src="../<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="100"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td><?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', '<?php echo addslashes(htmlspecialchars($product['description'])); ?>', '<?php echo number_format($product['price'], 0, ',', '.'); ?>', '<?php echo $product['category_id']; ?>')">Edit</button>
                                    
                                    <!-- Tombol Hapus -->
                                    <form method="POST" action="manage.php" style="display:inline;">
                                        <input type="hidden" name="delete_product" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- === Modal Edit Kategori === -->
        <div id="editCategoryModal">
            <div class="modal-content">
                <span class="close" onclick="closeCategoryModal()">&times;</span>
                <h2>Edit Category</h2>
                <form method="POST" action="manage.php">
                    <input type="hidden" name="edit_category" value="1">
                    <input type="hidden" name="edit_category_id" id="edit_category_id">

                    <label for="edit_category_name">Category Name:</label>
                    <input type="text" id="edit_category_name" name="edit_category_name" required>

                    <label for="edit_category_description">Description:</label>
                    <textarea id="edit_category_description" name="edit_category_description" rows="3"></textarea>

                    <button type="submit">Update Category</button>
                    <button type="button" onclick="closeCategoryModal()" style="background-color:#6c757d; margin-left:10px;">Cancel</button>
                </form>
            </div>
        </div>

        <!-- === Modal Edit Banner === -->
        <div id="editBannerModal">
            <div class="modal-content">
                <span class="close" onclick="closeBannerModal()">&times;</span>
                <h2>Edit Banner</h2>
                <form method="POST" action="manage.php" enctype="multipart/form-data">
                    <input type="hidden" name="edit_banner" value="1">
                    <input type="hidden" name="edit_banner_id" id="edit_banner_id">

                    <label for="edit_banner_alt_text">Alt Text:</label>
                    <input type="text" id="edit_banner_alt_text" name="edit_banner_alt_text">

                    <label for="edit_banner_image">Banner Image (Leave blank to keep existing):</label>
                    <input type="file" id="edit_banner_image" name="edit_banner_image" accept="image/*">

                    <label for="edit_banner_active">Active:</label>
                    <input type="checkbox" id="edit_banner_active" name="edit_banner_active">

                    <button type="submit">Update Banner</button>
                    <button type="button" onclick="closeBannerModal()" style="background-color:#6c757d; margin-left:10px;">Cancel</button>
                </form>
            </div>
        </div>

        <!-- === Modal Edit Produk === -->
        <div id="editProductModal">
            <div class="modal-content">
                <span class="close" onclick="closeProductModal()">&times;</span>
                <h2>Edit Product</h2>
                <form method="POST" action="manage.php" enctype="multipart/form-data">
                    <input type="hidden" name="edit_product" value="1">
                    <input type="hidden" name="edit_product_id" id="edit_product_id">

                    <label for="edit_product_name">Product Name:</label>
                    <input type="text" id="edit_product_name" name="edit_product_name" required>

                    <label for="edit_product_description">Description:</label>
                    <textarea id="edit_product_description" name="edit_product_description" rows="3"></textarea>

                    <label for="edit_product_price">Price (Rp.):</label>
                    <input type="text" id="edit_product_price" name="edit_product_price" required>

                    <label for="edit_product_category">Category:</label>
                    <select id="edit_product_category" name="edit_product_category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="edit_product_image">Product Image (Leave blank to keep existing):</label>
                    <input type="file" id="edit_product_image" name="edit_product_image" accept="image/*">

                    <button type="submit">Update Product</button>
                    <button type="button" onclick="closeProductModal()" style="background-color:#6c757d; margin-left:10px;">Cancel</button>
                </form>
            </div>
        </div>

        <!-- === JavaScript untuk Modal === -->
        <script>
            // === Modal Edit Kategori ===
            function editCategory(id, name, description) {
                document.getElementById('edit_category_id').value = id;
                document.getElementById('edit_category_name').value = name;
                document.getElementById('edit_category_description').value = description;
                document.getElementById('editCategoryModal').style.display = 'block';
            }

            function closeCategoryModal() {
                document.getElementById('editCategoryModal').style.display = 'none';
            }

            // === Modal Edit Banner ===
            function editBanner(id, alt_text, active) {
                document.getElementById('edit_banner_id').value = id;
                document.getElementById('edit_banner_alt_text').value = alt_text;
                document.getElementById('edit_banner_active').checked = active == 1 ? true : false;
                document.getElementById('editBannerModal').style.display = 'block';
            }

            function closeBannerModal() {
                document.getElementById('editBannerModal').style.display = 'none';
            }

            // === Modal Edit Produk ===
            function editProduct(id, name, description, price, category_id) {
                document.getElementById('edit_product_id').value = id;
                document.getElementById('edit_product_name').value = name;
                document.getElementById('edit_product_description').value = description;
                document.getElementById('edit_product_price').value = price;
                document.getElementById('edit_product_category').value = category_id;
                document.getElementById('editProductModal').style.display = 'block';
            }

            function closeProductModal() {
                document.getElementById('editProductModal').style.display = 'none';
            }

            // Tutup modal saat klik di luar konten modal
            window.onclick = function(event) {
                const categoryModal = document.getElementById('editCategoryModal');
                if (event.target == categoryModal) {
                    categoryModal.style.display = 'none';
                }

                const bannerModal = document.getElementById('editBannerModal');
                if (event.target == bannerModal) {
                    bannerModal.style.display = 'none';
                }

                const productModal = document.getElementById('editProductModal');
                if (event.target == productModal) {
                    productModal.style.display = 'none';
                }
            }
        </script>
    </div>
</body>
</html>
