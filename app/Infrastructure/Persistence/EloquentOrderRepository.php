<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Order\Order;
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

            foreach ($order->items() as $item) {
                $model->items()->create([
                    'product_id'   => $item->productId,
                    'product_name' => $item->productName,
                    'qty'          => $item->qty,
                    'unit_price'   => $item->unitPrice,
                ]);
            }

            return $order->withId($model->id);
        });
    }
}
