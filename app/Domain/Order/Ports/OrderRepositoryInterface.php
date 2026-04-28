<?php

namespace App\Domain\Order\Ports;

use App\Domain\Order\Order;

/** Port that keeps domain logic decoupled from Eloquent — swappable in tests without touching PlaceOrderHandler. */
interface OrderRepositoryInterface
{
    public function save(Order $order): Order;

    public function findById(int $id): Order;
}
