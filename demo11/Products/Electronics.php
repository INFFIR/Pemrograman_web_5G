<?php
// demo11/Products/Electronics.php

namespace Demo11\Products;

use Demo11\Abstracts\Product;

class Electronics extends Product
{
    private string $brand;
    private string $model;

    public function __construct(string $name, string $description, float $price, string $brand, string $model)
    {
        parent::__construct($name, $description, $price);
        $this->brand = $brand;
        $this->model = $model;
    }

    public function getType(): string
    {
        return "Elektronik";
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function __toString(): string
    {
        return parent::__toString() . " | Brand: {$this->brand} | Model: {$this->model}";
    }
}
