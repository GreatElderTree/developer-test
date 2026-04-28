<?php

namespace App\Application\Order;

/** Fixes: original passed the raw $request array directly to PDO — no type safety, no explicit contract. */
class PlaceOrderCommand
{
    /**
     * @param array<int, array{product_id: int, qty: int}> $items
     */
    public function __construct(
        public readonly string $customerEmail,
        public readonly array $items,
    ) {}
}
