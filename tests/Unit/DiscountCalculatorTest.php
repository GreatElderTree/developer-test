<?php

namespace Tests\Unit;

use App\Domain\Customer\Customer;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Discount\Rules\OrderTotalDiscountRule;
use App\Domain\Discount\Rules\PremiumCustomerDiscountRule;
use PHPUnit\Framework\TestCase;

class DiscountCalculatorTest extends TestCase
{
    private function makeCalculator(): DiscountCalculator
    {
        return new DiscountCalculator([
            new OrderTotalDiscountRule(),
            new PremiumCustomerDiscountRule(),
        ]);
    }

    private function makeCustomer(bool $isPremium): Customer
    {
        return new Customer(id: 1, name: 'Test', email: 'test@test.com', isPremium: $isPremium);
    }

    public function test_no_discount_for_small_order_guest(): void
    {
        $result = $this->makeCalculator()->calculate(5000, null);

        $this->assertSame('0.00', $result->percentage);
        $this->assertSame(0,      $result->amount);
        $this->assertSame(5000,   $result->total);
    }

    public function test_10_percent_discount_when_subtotal_over_100(): void
    {
        $result = $this->makeCalculator()->calculate(20000, null);

        $this->assertSame('10.00', $result->percentage);
        $this->assertSame(2000,    $result->amount);
        $this->assertSame(18000,   $result->total);
    }

    public function test_5_percent_discount_for_premium_customer_below_100(): void
    {
        $result = $this->makeCalculator()->calculate(8000, $this->makeCustomer(true));

        $this->assertSame('5.00', $result->percentage);
        $this->assertSame(400,    $result->amount);
        $this->assertSame(7600,   $result->total);
    }

    public function test_15_percent_discount_for_premium_customer_over_100(): void
    {
        $result = $this->makeCalculator()->calculate(20000, $this->makeCustomer(true));

        $this->assertSame('15.00', $result->percentage);
        $this->assertSame(3000,    $result->amount);
        $this->assertSame(17000,   $result->total);
    }

    public function test_no_extra_discount_for_non_premium_customer(): void
    {
        $result = $this->makeCalculator()->calculate(20000, $this->makeCustomer(false));

        $this->assertSame('10.00', $result->percentage);
        $this->assertSame(2000,    $result->amount);
        $this->assertSame(18000,   $result->total);
    }

    public function test_discount_is_capped_at_20_percent(): void
    {
        $calculator = new DiscountCalculator([
            new OrderTotalDiscountRule(),
            new PremiumCustomerDiscountRule(),
            new OrderTotalDiscountRule(), // +10% extra → would be 25%, capped to 20%
        ]);

        $result = $calculator->calculate(20000, $this->makeCustomer(true));

        $this->assertSame('20.00', $result->percentage);
        $this->assertSame(4000,    $result->amount);
        $this->assertSame(16000,   $result->total);
    }

    public function test_boundary_at_exactly_100_gets_no_order_discount(): void
    {
        $result = $this->makeCalculator()->calculate(10000, null);

        $this->assertSame('0.00', $result->percentage);
        $this->assertSame(0,      $result->amount);
        $this->assertSame(10000,  $result->total);
    }

    public function test_no_discount_when_calculator_has_no_rules(): void
    {
        $result = (new DiscountCalculator([]))->calculate(50000, $this->makeCustomer(true));

        $this->assertSame('0.00', $result->percentage);
        $this->assertSame(0,      $result->amount);
        $this->assertSame(50000,  $result->total);
    }
}
