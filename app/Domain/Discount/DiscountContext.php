<?php

namespace App\Domain\Discount;

use App\Domain\Customer\Customer;

/** Carries mutable discount state through the rule pipeline and enforces the 20% hard cap via addDiscount(). */
class DiscountContext
{
    private const MAX_DISCOUNT = 20.0;

    private float $accumulatedDiscount = 0.0;

    public function __construct(
        public readonly float $subtotal,
        public readonly ?Customer $customer,
    ) {}

    public function addDiscount(float $percentage): void
    {
        $this->accumulatedDiscount = min(self::MAX_DISCOUNT, $this->accumulatedDiscount + $percentage);
    }

    public function accumulatedDiscount(): float
    {
        return $this->accumulatedDiscount;
    }
}
