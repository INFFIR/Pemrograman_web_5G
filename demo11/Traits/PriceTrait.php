<?php
// demo11/Traits/PriceTrait.php

namespace Demo11\Traits;

trait PriceTrait
{
    protected float $price;

    public function setPrice(float $price): void
    {
        if ($price < 0) {
            throw new \InvalidArgumentException("Harga tidak boleh negatif.");
        }
        $this->price = $price;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
