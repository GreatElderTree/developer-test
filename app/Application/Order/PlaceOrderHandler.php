<?php

namespace App\Application\Order;

use App\Domain\Customer\Ports\CustomerRepositoryInterface;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\Ports\OrderNotifierInterface;
use App\Domain\Order\Ports\OrderRepositoryInterface;
use App\Domain\Product\Ports\ProductRepositoryInterface;
use App\Exceptions\InvalidOrderException;
use Illuminate\Support\Facades\Log;

/**
 * Fixes: original store() did everything (DB, math, mail) in one method.
 * Orchestrates product lookup (single batch query), discount calculation, persistence, and async notification.
 */
class PlaceOrderHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ProductRepositoryInterface  $productRepository,
        private readonly OrderRepositoryInterface    $orderRepository,
        private readonly DiscountCalculator          $discountCalculator,
        private readonly OrderNotifierInterface      $orderNotifier,
    ) {}

    public function handle(PlaceOrderCommand $command): Order
    {
        $customer   = $this->customerRepository->findByEmail($command->customerEmail);
        $guestEmail = $customer ? null : $command->customerEmail;

        $productIds = array_column($command->items, 'product_id');
        $products   = $this->productRepository->findByIds($productIds);

        foreach ($productIds as $id) {
            if (! isset($products[$id])) {
                throw new InvalidOrderException("Product ID {$id} not found.");
            }
        }

        $subtotal   = 0;
        $orderItems = [];

        foreach ($command->items as $item) {
            $product     = $products[$item['product_id']];
            $subtotal   += $product->price * $item['qty'];
            $orderItems[] = new OrderItem(
                productId:   $product->id,
                productName: $product->name,
                qty:         $item['qty'],
                unitPrice:   $product->price,
            );
        }

        $discount = $this->discountCalculator->calculate($subtotal, $customer);
        $order    = Order::place($orderItems, $customer, $guestEmail, $discount);
        $order    = $this->orderRepository->save($order);

        $recipientEmail = $customer?->email ?? $guestEmail ?? '';

        Log::info('Order placed', [
            'order_id'            => $order->id(),
            'email'               => $recipientEmail,
            'customer_type'       => $customer
                ? ($customer->isPremium ? 'premium' : 'registered')
                : 'guest',
            'subtotal'            => $order->subtotal(),
            'discount_percentage' => $order->discountPercentage(),
            'total'               => $order->total(),
        ]);

        $this->orderNotifier->notifyOrderPlaced((int) $order->id(), $recipientEmail);

        return $order;
    }
}
