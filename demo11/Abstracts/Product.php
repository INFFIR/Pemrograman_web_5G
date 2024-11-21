<?php
// demo11/Abstracts/Product.php

namespace Demo11\Abstracts;

use Demo11\Traits\PriceTrait;

abstract class Product
{
    use PriceTrait;

    protected string $name;
    protected string $description;

    public function __construct(string $name, string $description, float $price)
    {
        $this->name = $name;
        $this->description = $description;
        $this->setPrice($price);
    }

    // Metode abstrak untuk mendapatkan tipe produk
    abstract public function getType(): string;

    public function __toString(): string
    {
        return "{$this->getType()} - {$this->name}: {$this->description} | Harga: Rp " . number_format($this->price, 2, ',', '.');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
