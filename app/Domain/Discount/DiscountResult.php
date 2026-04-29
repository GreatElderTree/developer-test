<?php

namespace App\Domain\Discount;

/** Immutable output of the discount pipeline: percentage, amount, and final total derived from subtotal. */
class DiscountResult
{
    public readonly int $amount;
    public readonly int $total;

    public function __construct(
        public readonly string $percentage,
        public readonly int $subtotal,
    ) {
        $this->amount = (int) round($subtotal * (float) $percentage / 100);
        $this->total  = $subtotal - $this->amount;
    }
}
