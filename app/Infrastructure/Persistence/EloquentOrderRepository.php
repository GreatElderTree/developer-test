<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\Ports\OrderRepositoryInterface;
use App\Infrastructure\Persistence\Models\OrderItemModel;
use App\Infrastructure\Persistence\Models\OrderModel;
use Illuminate\Support\Facades\DB;

/** Fixes: original bare INSERT had no transaction — if item inserts failed, an orphan order row remained. DB::transaction() makes it all-or-nothing. */
class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $model = OrderModel::create([
                'customer_id'         => $order->customerId(),
                'guest_email'         => $order->guestEmail(),
                'subtotal'            => $order->subtotal(),
                'discount_percentage' => $order->discountPercentage(),
                'discount_amount'     => $order->discountAmount(),
                'total'               => $order->total(),
                'status'              => $order->status(),
            ]);

            $now = now();

            DB::table('order_items')->insert(
                array_map(fn (OrderItem $item) => [
                    'order_id'     => $model->id,
                    'product_id'   => $item->productId,
                    'product_name' => $item->productName,
                    'qty'          => $item->qty,
                    'unit_price'   => $item->unitPrice,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ], $order->items())
            );

            return $order->withId($model->id);
        });
    }

    public function findById(int $id): Order
    {
        $model = OrderModel::with('items')->findOrFail($id);

        $items = $model->items->map(fn (OrderItemModel $item) => new OrderItem(
            productId:   $item->product_id,
            productName: $item->product_name,
            qty:         $item->qty,
            unitPrice:   (string) $item->unit_price,
        ))->all();

        return new Order(
            id:                 $model->id,
            customerId:         $model->customer_id,
            guestEmail:         $model->guest_email,
            subtotal:           (string) $model->subtotal,
            discountPercentage: (string) $model->discount_percentage,
            discountAmount:     (string) $model->discount_amount,
            total:              (string) $model->total,
            status:             $model->status,
            items:              $items,
        );
    }
}
