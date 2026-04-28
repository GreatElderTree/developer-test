<?php

namespace App\Domain\Discount\Rules;

use App\Domain\Discount\DiscountContext;

/** Fixes: original hardcoded one total into a single INSERT — this contract lets any number of discount rules be added without touching the calculator. */
interface DiscountRuleInterface
{
    /**
     * Return the additional discount percentage this rule contributes (e.g. 5.0 = 5%).
     * DiscountContext enforces the 20% cap via addDiscount().
     */
    public function apply(DiscountContext $context): float;
}
