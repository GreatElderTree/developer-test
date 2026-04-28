<?php

namespace App\Domain\Order\Ports;

/** Port that decouples domain logic from mail transport — test can bind LaravelMailOrderNotifier or a no-op stub. */
interface OrderNotifierInterface
{
    public function notifyOrderPlaced(int $orderId, string $recipientEmail): void;
}
