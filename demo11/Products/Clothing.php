<?php
// demo11/Products/Clothing.php

namespace Demo11\Products;

use Demo11\Abstracts\Product;

class Clothing extends Product
{
    private string $size;
    private string $color;

    public function __construct(string $name, string $description, float $price, string $size, string $color)
    {
        parent::__construct($name, $description, $price);
        $this->size = $size;
        $this->color = $color;
    }

    public function getType(): string
    {
        return "Pakaian";
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function __toString(): string
    {
        return parent::__toString() . " | Ukuran: {$this->size} | Warna: {$this->color}";
    }
}
