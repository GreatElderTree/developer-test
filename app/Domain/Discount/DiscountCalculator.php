<?php

namespace App\Domain\Discount;

use App\Domain\Customer\Customer;
use App\Domain\Discount\Rules\DiscountRuleInterface;

/** Fixes: original had no discount logic at all — runs each DiscountRuleInterface in order and emits an immutable DiscountResult. */
class DiscountCalculator
{
    /** @param DiscountRuleInterface[] $rules */
    public function __construct(private readonly array $rules) {}

    public function calculate(float $subtotal, ?Customer $customer): DiscountResult
    {
        $context = new DiscountContext($subtotal, $customer);

        foreach ($this->rules as $rule) {
            $context->addDiscount($rule->apply($context));
        }

        return new DiscountResult($context->accumulatedDiscount(), $subtotal);
    }
}
