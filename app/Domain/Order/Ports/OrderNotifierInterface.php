<?php

namespace App\Domain\Order\Ports;

use App\Domain\Order\Order;

/** Port that decouples domain logic from mail transport — test can bind LaravelMailOrderNotifier or a no-op stub. */
interface OrderNotifierInterface
{
    public function notifyOrderPlaced(Order $order, string $recipientEmail): void;
}
