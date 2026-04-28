<?php

namespace App\Providers;

use App\Domain\Customer\Ports\CustomerRepositoryInterface;
use App\Domain\Discount\DiscountCalculator;
use App\Domain\Discount\Rules\OrderTotalDiscountRule;
use App\Domain\Discount\Rules\PremiumCustomerDiscountRule;
use App\Domain\Order\Ports\OrderNotifierInterface;
use App\Domain\Order\Ports\OrderRepositoryInterface;
use App\Domain\Product\Ports\ProductRepositoryInterface;
use App\Infrastructure\Notification\LaravelMailOrderNotifier;
use App\Infrastructure\Persistence\EloquentCustomerRepository;
use App\Infrastructure\Persistence\EloquentOrderRepository;
use App\Infrastructure\Persistence\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

/** Wires ports to their adapters and registers the discount pipeline — adding a rule is a one-liner here, no changes elsewhere. */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind ports to their infrastructure adapters
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(ProductRepositoryInterface::class,  EloquentProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class,    EloquentOrderRepository::class);
        $this->app->bind(OrderNotifierInterface::class,      LaravelMailOrderNotifier::class);

        // Discount rules — add new rules here to extend the pipeline
        $this->app->singleton(DiscountCalculator::class, fn () => new DiscountCalculator([
            new OrderTotalDiscountRule(),
            new PremiumCustomerDiscountRule(),
        ]));
    }
}
