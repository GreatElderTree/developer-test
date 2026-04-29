<?php

namespace App\Domain\Discount;

use App\Domain\Customer\Customer;
use App\Domain\Discount\Rules\DiscountRuleInterface;

/** Fixes: original had no discount logic at all — runs each DiscountRuleInterface in order and emits an immutable DiscountResult. */
class DiscountCalculator
{
    /** @param DiscountRuleInterface[] $rules */
    public function __construct(private readonly array $rules) {}

    public function calculate(string $subtotal, ?Customer $customer): DiscountResult
    {
        $context = new DiscountContext($subtotal, $customer);

        foreach ($this->rules as $rule) {
            $context = $context->withDiscount($rule->apply($context));
        }

        return new DiscountResult(number_format($context->accumulatedDiscount(), 2, '.', ''), $subtotal);
    }
}
