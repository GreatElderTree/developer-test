<?php

namespace App\Domain\Discount\Rules;

use App\Domain\Discount\DiscountContext;

/** New: original had no customer tiers — grants an extra 5% for premium customers on top of any other rules. */
class PremiumCustomerDiscountRule implements DiscountRuleInterface
{
    public function apply(DiscountContext $context): float
    {
        return $context->customer?->isPremium ? 5.0 : 0.0;
    }
}
