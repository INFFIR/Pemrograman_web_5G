<?php
$tinggi = 5; // Tinggi segitiga

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
?>
