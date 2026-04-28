<?php

namespace App\Mail;

use App\Domain\Order\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Fixes: original used a plain mail() with a one-liner body — this is a properly queued Mailable with a full Blade template. */
class OrderConfirmation extends Mailable
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order Confirmed — #' . $this->order->id());
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-confirmation');
    }
}
