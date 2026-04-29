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
        $result = $this->makeCalculator()->calculate('50.00', null);

        $this->assertSame('0.00',  $result->percentage);
        $this->assertSame('0.00',  $result->amount);
        $this->assertSame('50.00', $result->total);
    }

    public function test_10_percent_discount_when_subtotal_over_100(): void
    {
        $result = $this->makeCalculator()->calculate('200.00', null);

        $this->assertSame('10.00',  $result->percentage);
        $this->assertSame('20.00',  $result->amount);
        $this->assertSame('180.00', $result->total);
    }

    public function test_5_percent_discount_for_premium_customer_below_100(): void
    {
        $result = $this->makeCalculator()->calculate('80.00', $this->makeCustomer(true));

        $this->assertSame('5.00',  $result->percentage);
        $this->assertSame('4.00',  $result->amount);
        $this->assertSame('76.00', $result->total);
    }

    public function test_15_percent_discount_for_premium_customer_over_100(): void
    {
        $result = $this->makeCalculator()->calculate('200.00', $this->makeCustomer(true));

        $this->assertSame('15.00',  $result->percentage);
        $this->assertSame('30.00',  $result->amount);
        $this->assertSame('170.00', $result->total);
    }

    public function test_no_extra_discount_for_non_premium_customer(): void
    {
        $result = $this->makeCalculator()->calculate('200.00', $this->makeCustomer(false));

        $this->assertSame('10.00',  $result->percentage);
        $this->assertSame('20.00',  $result->amount);
        $this->assertSame('180.00', $result->total);
    }

    public function test_discount_is_capped_at_20_percent(): void
    {
        $calculator = new DiscountCalculator([
            new OrderTotalDiscountRule(),
            new PremiumCustomerDiscountRule(),
            new OrderTotalDiscountRule(), // +10% extra → would be 25%, capped to 20%
        ]);

        $result = $calculator->calculate('200.00', $this->makeCustomer(true));

        $this->assertSame('20.00',  $result->percentage);
        $this->assertSame('40.00',  $result->amount);
        $this->assertSame('160.00', $result->total);
    }

    public function test_boundary_at_exactly_100_gets_no_order_discount(): void
    {
        $result = $this->makeCalculator()->calculate('100.00', null);

        $this->assertSame('0.00',   $result->percentage);
        $this->assertSame('0.00',   $result->amount);
        $this->assertSame('100.00', $result->total);
    }

    public function test_no_discount_when_calculator_has_no_rules(): void
    {
        $result = (new DiscountCalculator([]))->calculate('500.00', $this->makeCustomer(true));

        $this->assertSame('0.00',   $result->percentage);
        $this->assertSame('0.00',   $result->amount);
        $this->assertSame('500.00', $result->total);
    }
}
