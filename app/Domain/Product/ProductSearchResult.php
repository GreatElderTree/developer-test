<?php

namespace App\Domain\Product;

/** Paginated result from ProductRepositoryInterface::search(). */
class ProductSearchResult
{
    /** @param Product[] $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage,
    ) {}
}
