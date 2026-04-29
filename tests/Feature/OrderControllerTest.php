<?php

namespace Tests\Feature;

use App\Domain\Order\Ports\OrderNotifierInterface;
use App\Infrastructure\Notification\LaravelMailOrderNotifier;
use App\Infrastructure\Persistence\Models\CustomerModel;
use App\Infrastructure\Persistence\Models\ProductModel;
use App\Mail\OrderConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(int $price = 5000): ProductModel
    {
        return ProductModel::create(['name' => 'Widget', 'price' => $price]);
    }

    public function test_order_form_renders(): void
    {
        $this->get(route('orders.create'))
            ->assertOk()
            ->assertViewIs('orders.create');
    }

    public function test_guest_can_place_order(): void
    {
        $product = $this->createProduct();

        $this->post(route('orders.store'), [
            'customer_email' => 'guest@example.com',
            'items'          => [['product_id' => $product->id, 'qty' => 1]],
        ])
            ->assertRedirect(route('orders.create'))
            ->assertSessionHas('success', fn (string $v) => str_contains($v, '€50.00'));

        $this->assertDatabaseHas('orders', [
            'guest_email' => 'guest@example.com',
            'customer_id' => null,
            'total'       => 5000,
        ]);
    }

    public function test_premium_customer_gets_discount(): void
    {
        $customer = CustomerModel::create(['name' => 'VIP', 'email' => 'vip@test.com', 'is_premium' => true]);
        $product  = $this->createProduct(6000);

        $this->post(route('orders.store'), [
            'customer_email' => 'vip@test.com',
            'items'          => [['product_id' => $product->id, 'qty' => 2]],
        ])
            ->assertRedirect(route('orders.create'))
            ->assertSessionHas('success', fn (string $v) => str_contains($v, '15%'));

        $this->assertDatabaseHas('orders', [
            'customer_id'         => $customer->id,
            'discount_percentage' => '15.00',
            'total'               => 10200,
        ]);
    }

    public function test_order_confirmation_mail_is_queued(): void
    {
        Mail::fake();
        $this->app->bind(OrderNotifierInterface::class, LaravelMailOrderNotifier::class);

        $product = $this->createProduct();

        $this->post(route('orders.store'), [
            'customer_email' => 'buyer@example.com',
            'items'          => [['product_id' => $product->id, 'qty' => 1]],
        ]);

        Mail::assertQueued(OrderConfirmation::class, fn ($mail) =>
            $mail->hasTo('buyer@example.com')
        );
    }

    public function test_order_confirmation_mail_renders_correctly(): void
    {
        $this->app->bind(OrderNotifierInterface::class, LaravelMailOrderNotifier::class);

        $product = $this->createProduct(3333);

        $this->post(route('orders.store'), [
            'customer_email' => 'buyer@example.com',
            'items'          => [['product_id' => $product->id, 'qty' => 3]],
        ]);

        $order = \App\Infrastructure\Persistence\Models\OrderModel::first();
        $mailable = app(OrderConfirmation::class, ['orderId' => $order->id]);

        $html = $mailable->render();
        $this->assertStringContainsString((string) $order->id, $html);
        $this->assertStringContainsString('99.99', $html);
    }

    public function test_validation_rejects_missing_email(): void
    {
        $this->post(route('orders.store'), ['items' => [['product_id' => 1, 'qty' => 1]]])
            ->assertSessionHasErrors('customer_email');
    }

    public function test_validation_rejects_missing_items(): void
    {
        $this->post(route('orders.store'), ['customer_email' => 'test@example.com'])
            ->assertSessionHasErrors('items');
    }

    public function test_validation_rejects_empty_items_array(): void
    {
        $this->post(route('orders.store'), [
            'customer_email' => 'test@example.com',
            'items'          => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_validation_rejects_invalid_product(): void
    {
        $this->post(route('orders.store'), [
            'customer_email' => 'test@example.com',
            'items'          => [['product_id' => 9999, 'qty' => 1]],
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_validation_rejects_zero_qty(): void
    {
        $product = $this->createProduct();

        $this->post(route('orders.store'), [
            'customer_email' => 'test@example.com',
            'items'          => [['product_id' => $product->id, 'qty' => 0]],
        ])->assertSessionHasErrors('items.0.qty');
    }

    public function test_validation_rejects_qty_over_maximum(): void
    {
        $product = $this->createProduct();

        $this->post(route('orders.store'), [
            'customer_email' => 'test@example.com',
            'items'          => [['product_id' => $product->id, 'qty' => 1001]],
        ])->assertSessionHasErrors('items.0.qty');
    }

    public function test_validation_rejects_duplicate_product_ids(): void
    {
        $product = $this->createProduct();

        $this->post(route('orders.store'), [
            'customer_email' => 'test@example.com',
            'items'          => [
                ['product_id' => $product->id, 'qty' => 1],
                ['product_id' => $product->id, 'qty' => 2],
            ],
        ])->assertSessionHasErrors('items.0.product_id');
    }
}
