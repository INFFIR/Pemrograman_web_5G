<?php
// demo11/index.php

// Meng-include semua file kelas secara manual dengan urutan yang benar
require_once 'Traits/PriceTrait.php';      
require_once 'Abstracts/Product.php';      
require_once 'Products/Electronics.php';  
require_once 'Products/Clothing.php';       

// Menggunakan namespace untuk kelas yang diperlukan
use Demo11\Products\Electronics;
use Demo11\Products\Clothing;

try {
    // Membuat produk elektronik
    $laptop = new Electronics(
        "Laptop ABC",
        "Laptop dengan performa tinggi untuk kebutuhan sehari-hari.",
        12000000.00,
        "BrandX",
        "ModelX1"
    );

    $smartphone = new Electronics(
        "Smartphone DEF",
        "Smartphone dengan kamera canggih dan baterai tahan lama.",
        7000000.00,
        "BrandY",
        "ModelY1"
    );

    // Membuat produk pakaian
    $tShirt = new Clothing(
        "T-Shirt Pria",
        "T-shirt nyaman dengan bahan katun berkualitas.",
        150000.00,
        "M",
        "Hitam"
    );

    $jeans = new Clothing(
        "Jeans Wanita",
        "Jeans stylish dengan desain modern.",
        300000.00,
        "S",
        "Biru"
    );


    $products = [$laptop, $smartphone, $tShirt, $jeans];

    foreach ($products as $product) {
        echo $product . PHP_EOL;
    }

    echo PHP_EOL;

    echo "{$laptop->getName()} harga: Rp " . number_format($laptop->getPrice(), 2, ',', '.') . PHP_EOL;


    $laptop->setPrice(11000000.00);
    echo "Setelah update harga: " . $laptop . PHP_EOL;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

