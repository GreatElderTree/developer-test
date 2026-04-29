<?php

namespace App\Domain\Discount;

use App\Domain\Customer\Customer;

/** Immutable snapshot of discount state; each rule produces a new instance via withDiscount(). */
class DiscountContext
{
    private const MAX_DISCOUNT = 20.0;

    public function __construct(
        public readonly string $subtotal,
        public readonly ?Customer $customer,
        private readonly float $accumulatedDiscount = 0.0,
    ) {}

    public function withDiscount(float $percentage): self
    {
        return new self(
            subtotal:            $this->subtotal,
            customer:            $this->customer,
            accumulatedDiscount: min(self::MAX_DISCOUNT, $this->accumulatedDiscount + $percentage),
        );
    }

    public function accumulatedDiscount(): float
    {
        return $this->accumulatedDiscount;
    }
}
