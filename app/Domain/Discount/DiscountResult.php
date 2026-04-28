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
        $amount       = bcmul((string) $subtotal, bcdiv((string) $percentage, '100', 10), 2);
        $this->amount = (float) $amount;
        $this->total  = (float) bcsub((string) $subtotal, $amount, 2);
    }
}
