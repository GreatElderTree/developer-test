<?php

namespace App\Domain\Order;

/** Fixes: original never persisted line items — snapshots product name and price at order time so history survives catalogue changes. */
class OrderItem
{
    public readonly float $lineTotal;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $qty,
        public readonly float $unitPrice,
    ) {
        $this->lineTotal = round($qty * $unitPrice, 2);
    }
}
