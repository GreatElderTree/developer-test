<?php

namespace App\Domain\Discount;

/** Immutable output of the discount pipeline: percentage, amount, and final total derived from subtotal. */
class DiscountResult
{
    public readonly float $amount;
    public readonly float $total;

    public function __construct(
        public readonly float $percentage,
        public readonly float $subtotal,
    ) {
        $this->amount = round($subtotal * $percentage / 100, 2);
        $this->total  = round($subtotal - $this->amount, 2);
    }
}
