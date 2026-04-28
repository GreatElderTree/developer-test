<?php

namespace App\Domain\Product\Ports;

use App\Domain\Product\Product;
use App\Domain\Product\ProductSearchResult;

/** Port exposing batch lookup (findByIds) and paginated search — prevents N+1 queries and keeps the domain free of Eloquent. */
interface ProductRepositoryInterface
{
    /** @return Product[] keyed by id */
    public function findByIds(array $ids): array;

    public function search(string $query, int $perPage = 10, int $page = 1): ProductSearchResult;
}
