<?php

namespace App\Http\Controllers;

use App\Application\Order\PlaceOrderCommand;
use App\Application\Order\PlaceOrderHandler;
use App\Domain\Product\Ports\ProductRepositoryInterface;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Fixes: original store() opened a raw PDO connection, did SQL injection-prone queries, called die(), and blocked on mail() — all in one method. */
class OrderController extends Controller
{
    public function __construct(
        private readonly PlaceOrderHandler $placeOrderHandler,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function create(): View
    {
        $products = collect($this->productRepository->findAll());

        return view('orders.create', compact('products'));
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->placeOrderHandler->handle(
            new PlaceOrderCommand(
                customerEmail: $request->validated('customer_email'),
                items:         $request->validated('items'),
            )
        );

        $discountNote = $order->discountPercentage() > 0
            ? " ({$order->discountPercentage()}% discount applied)"
            : '';

        return redirect()->route('orders.create')
            ->with('success', "Order #{$order->id()} placed! Total: €"
                . number_format($order->total(), 2) . $discountNote);
    }
}
