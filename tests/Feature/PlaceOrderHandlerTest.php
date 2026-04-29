<?php

namespace Tests\Feature;

use App\Application\Order\PlaceOrderCommand;
use App\Application\Order\PlaceOrderHandler;
use App\Exceptions\InvalidOrderException;
use App\Infrastructure\Persistence\Models\CustomerModel;
use App\Infrastructure\Persistence\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceOrderHandlerTest extends TestCase
{
    use RefreshDatabase;

    private PlaceOrderHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = app(PlaceOrderHandler::class);
    }

    private function createProduct(string $name = 'Test Product', int $price = 5000): ProductModel
    {
        return ProductModel::create(['name' => $name, 'price' => $price]);
    }

    public function test_places_order_for_guest_with_correct_totals(): void
    {
        $product = $this->createProduct();

        $order = $this->handler->handle(new PlaceOrderCommand(
            customerEmail: 'guest@example.com',
            items:         [['product_id' => $product->id, 'qty' => 2]],
        ));

        $this->assertSame(10000, $order->subtotal());
        $this->assertSame('0.00', $order->discountPercentage());
        $this->assertSame(0,     $order->discountAmount());
        $this->assertSame(10000, $order->total());
        $this->assertNull($order->customerId());
        $this->assertEquals('guest@example.com', $order->guestEmail());

        $this->assertDatabaseHas('orders', [
            'guest_email'         => 'guest@example.com',
            'customer_id'         => null,
            'subtotal'            => 10000,
            'discount_percentage' => '0.00',
            'discount_amount'     => 0,
            'total'               => 10000,
            'status'              => 'confirmed',
        ]);
    }

    public function test_applies_order_total_discount_over_100(): void
    {
        $product = $this->createProduct(price: 6000);

        $order = $this->handler->handle(new PlaceOrderCommand(
            customerEmail: 'guest@example.com',
            items:         [['product_id' => $product->id, 'qty' => 2]],
        ));

        $this->assertSame(12000, $order->subtotal());
        $this->assertSame('10.00', $order->discountPercentage());
        $this->assertSame(1200,  $order->discountAmount());
        $this->assertSame(10800, $order->total());

        $this->assertDatabaseHas('orders', [
            'subtotal'            => 12000,
            'discount_percentage' => '10.00',
            'discount_amount'     => 1200,
            'total'               => 10800,
        ]);
    }

    public function test_applies_premium_discount_on_top(): void
    {
        $customer = CustomerModel::create(['name' => 'VIP', 'email' => 'vip@test.com', 'is_premium' => true]);
        $product  = $this->createProduct(price: 6000);

        $order = $this->handler->handle(new PlaceOrderCommand(
            customerEmail: $customer->email,
            items:         [['product_id' => $product->id, 'qty' => 2]],
        ));

        $this->assertSame('15.00', $order->discountPercentage());
        $this->assertSame(1800,    $order->discountAmount());
        $this->assertSame(10200,   $order->total());
        $this->assertEquals($customer->id, $order->customerId());
        $this->assertNull($order->guestEmail());
    }

    public function test_registered_non_premium_customer_order(): void
    {
        $customer = CustomerModel::create(['name' => 'Regular', 'email' => 'regular@test.com', 'is_premium' => false]);
        $product  = $this->createProduct(price: 4000);

        $order = $this->handler->handle(new PlaceOrderCommand(
            customerEmail: $customer->email,
            items:         [['product_id' => $product->id, 'qty' => 2]],
        ));

        $this->assertEquals($customer->id, $order->customerId());
        $this->assertNull($order->guestEmail());
        $this->assertSame(8000,   $order->subtotal());
        $this->assertSame('0.00', $order->discountPercentage());
        $this->assertSame(0,      $order->discountAmount());
        $this->assertSame(8000,   $order->total());
    }

    public function test_persists_order_items_with_name_and_price(): void
    {
        $p1 = $this->createProduct('Keyboard', 2000);
        $p2 = $this->createProduct('Mouse', 3000);

        $order = $this->handler->handle(new PlaceOrderCommand(
            customerEmail: 'test@example.com',
            items:         [
                ['product_id' => $p1->id, 'qty' => 3],
                ['product_id' => $p2->id, 'qty' => 1],
            ],
        ));

        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('order_items', [
            'order_id'     => $order->id(),
            'product_id'   => $p1->id,
            'product_name' => 'Keyboard',
            'qty'          => 3,
            'unit_price'   => 2000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id'     => $order->id(),
            'product_id'   => $p2->id,
            'product_name' => 'Mouse',
            'qty'          => 1,
            'unit_price'   => 3000,
        ]);
    }

    public function test_saved_order_can_be_reloaded_by_id(): void
    {
        $customer = CustomerModel::create(['name' => 'VIP', 'email' => 'vip@test.com', 'is_premium' => true]);
        $p1       = $this->createProduct('Keyboard', 2000);
        $p2       = $this->createProduct('Mouse', 3000);

        $order = $this->handler->handle(new PlaceOrderCommand(
            customerEmail: $customer->email,
            items:         [
                ['product_id' => $p1->id, 'qty' => 2],
                ['product_id' => $p2->id, 'qty' => 1],
            ],
        ));

        $repo     = app(\App\Domain\Order\Ports\OrderRepositoryInterface::class);
        $reloaded = $repo->findById($order->id());

        $this->assertEquals($order->id(),                $reloaded->id());
        $this->assertEquals($order->total(),             $reloaded->total());
        $this->assertEquals($order->discountPercentage(), $reloaded->discountPercentage());
        $this->assertEquals($customer->id,               $reloaded->customerId());
        $this->assertNull($reloaded->guestEmail());
        $this->assertCount(2, $reloaded->items());

        $names = array_map(fn ($i) => $i->productName, $reloaded->items());
        $this->assertContains('Keyboard', $names);
        $this->assertContains('Mouse',    $names);
    }

    public function test_throws_when_product_not_found(): void
    {
        $this->expectException(InvalidOrderException::class);

        try {
            $this->handler->handle(new PlaceOrderCommand(
                customerEmail: 'test@example.com',
                items:         [['product_id' => 9999, 'qty' => 1]],
            ));
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertDatabaseCount('order_items', 0);
        }
    }
}
