<?php

include "Controllers/ProductController.php";

use Controller\ProductController;

$productController = new ProductController;

echo $productController->getAllProduct();

echo "<br><br>";
$tinggi = 6; // Tinggi segitiga

// Segitiga sama sisi
for ($i = 1; $i <= $tinggi; $i++) {
    // Loop untuk menambahkan spasi di awal setiap baris
    for ($j = $tinggi; $j > $i; $j--) {
        echo "&nbsp;";
    }
    // Loop untuk menampilkan bintang
    for ($k = 1; $k <= (2 * $i - 1); $k++) {
        echo "*";
    }
    echo "<br>";
}

// Segitiga sama sisi terbalik
for ($i = $tinggi; $i >= 1; $i--) {
    // Loop untuk menambahkan spasi di awal setiap baris
    for ($j = $tinggi; $j > $i; $j--) {
        echo "&nbsp;";
    }
    // Loop untuk menampilkan bintang
    for ($k = 1; $k <= (2 * $i - 1); $k++) {
        echo "*";
    }
    echo "<br>";
}
?>