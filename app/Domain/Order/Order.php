<?php

namespace App\Domain\Order;

use App\Domain\Customer\Customer;
use App\Domain\Discount\DiscountResult;

/** Fixes: original stored only a bare total — this domain object captures customer identity, guest email, discount breakdown, status, and line items. */
class Order
{
    /**
     * @param OrderItem[] $items
     */
    public function __construct(
        private readonly ?int $id,
        private readonly ?int $customerId,
        private readonly ?string $guestEmail,
        private readonly int $subtotal,
        private readonly string $discountPercentage,
        private readonly int $discountAmount,
        private readonly int $total,
        private readonly string $status,
        private readonly array $items,
    ) {}

    public static function place(
        array $items,
        ?Customer $customer,
        ?string $guestEmail,
        DiscountResult $discount,
    ): self {
        return new self(
            id:                 null,
            customerId:         $customer?->id,
            guestEmail:         $customer ? null : $guestEmail,
            subtotal:           $discount->subtotal,
            discountPercentage: $discount->percentage,
            discountAmount:     $discount->amount,
            total:              $discount->total,
            status:             'confirmed',
            items:              $items,
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id:                 $id,
            customerId:         $this->customerId,
            guestEmail:         $this->guestEmail,
            subtotal:           $this->subtotal,
            discountPercentage: $this->discountPercentage,
            discountAmount:     $this->discountAmount,
            total:              $this->total,
            status:             $this->status,
            items:              $this->items,
        );
    }

    public function id(): ?int                { return $this->id; }
    public function customerId(): ?int        { return $this->customerId; }
    public function guestEmail(): ?string     { return $this->guestEmail; }
    public function subtotal(): int           { return $this->subtotal; }
    public function discountPercentage(): string { return $this->discountPercentage; }
    public function discountAmount(): int     { return $this->discountAmount; }
    public function total(): int              { return $this->total; }
    public function status(): string          { return $this->status; }
    /** @return OrderItem[] */
    public function items(): array            { return $this->items; }
}
