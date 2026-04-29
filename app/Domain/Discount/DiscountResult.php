<?php

namespace App\Domain\Discount;

/** Immutable output of the discount pipeline: percentage, amount, and final total derived from subtotal. */
class DiscountResult
{
    public readonly string $amount;
    public readonly string $total;

    public function __construct(
        public readonly string $percentage,
        public readonly string $subtotal,
    ) {
        $amount       = bcmul($subtotal, bcdiv($percentage, '100', 10), 2);
        $this->amount = $amount;
        $this->total  = bcsub($subtotal, $amount, 2);
    }
}
