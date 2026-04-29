<?php

namespace App\Domain\Product;

class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $price,
    ) {}
}
