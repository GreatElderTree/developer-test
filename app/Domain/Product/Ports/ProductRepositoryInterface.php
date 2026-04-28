<?php

namespace App\Domain\Product\Ports;

use App\Domain\Product\Product;

/** Port exposing batch lookup (findByIds) — prevents N+1 queries and keeps the domain free of Eloquent. */
interface ProductRepositoryInterface
{
    /** @return Product[] keyed by id */
    public function findByIds(array $ids): array;

    /** @return Product[] */
    public function findAll(): array;
}
