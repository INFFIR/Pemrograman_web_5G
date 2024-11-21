<?php

function printNumbers($n) {
    for ($i = 1; $i <= $n; $i++) {
        if ($i % 4 == 0 && $i % 6 == 0) {
            echo "Pemrograman Website 2024" . PHP_EOL;
        } elseif ($i % 5 == 0) {
            echo "2024" . PHP_EOL;
        } elseif ($i % 4 == 0) {
            echo "Pemrograman" . PHP_EOL;
        } elseif ($i % 6 == 0) {
            echo "Website" . PHP_EOL;
        } else {
            echo $i . PHP_EOL;
        }
    }
}

printNumbers(20);

?>