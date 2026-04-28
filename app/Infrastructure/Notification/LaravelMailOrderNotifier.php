<?php

namespace App\Infrastructure\Notification;

use App\Domain\Order\Ports\OrderNotifierInterface;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Fixes: original called mail() inline, blocking the HTTP response until the SMTP handshake completed — this queues the mailable to Redis for async delivery. */
class LaravelMailOrderNotifier implements OrderNotifierInterface
{
    public function notifyOrderPlaced(int $orderId, string $recipientEmail): void
    {
        if (empty($recipientEmail)) {
            Log::warning('Order notification skipped: no recipient email', ['order_id' => $orderId]);
            return;
        }

        try {
            Mail::to($recipientEmail)->queue(new OrderConfirmation($orderId));

            Log::info('Order confirmation queued', [
                'order_id' => $orderId,
                'email'    => $recipientEmail,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to queue order confirmation', [
                'order_id'  => $orderId,
                'email'     => $recipientEmail,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
