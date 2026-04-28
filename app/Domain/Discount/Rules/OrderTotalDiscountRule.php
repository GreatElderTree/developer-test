<?php

namespace App\Domain\Discount\Rules;

use App\Domain\Discount\DiscountContext;

/** New: original had no discount logic — grants 10% when subtotal exceeds €100. */
class OrderTotalDiscountRule implements DiscountRuleInterface
{
    public function apply(DiscountContext $context): float
    {
        return $context->subtotal > 100 ? 10.0 : 0.0;
    }
}
