# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Context

Laravel 11 Order Management System. All commands run inside Docker containers. The original assignment starting point (`index.php`) has been replaced by a full Laravel application.

## Commands

```bash
# Start containers
docker compose up -d --build

# Run all tests (SQLite in-memory, no extra setup)
docker compose exec app php artisan test

# Run a single test file
docker compose exec app php artisan test tests/Feature/PlaceOrderHandlerTest.php

# Run a single test by name
docker compose exec app php artisan test --filter=test_applies_premium_discount_on_top

# Run migrations
docker compose exec app php artisan migrate --seed

# Create a customer interactively
docker compose exec app php artisan customer:create

# Process queued jobs (already running as the `queue` container)
docker compose exec app php artisan queue:work
```

## Architecture

### Discount pipeline (`app/Domain/Discount/`)

The core extensibility point. `DiscountCalculator` holds an ordered array of `DiscountRuleInterface` implementations. Each rule returns an additional percentage; the calculator accumulates and caps at 20%. Rules are registered in `AppServiceProvider::register()` — adding a new rule is a one-liner there.

- `OrderTotalDiscountRule` — subtotal > 100 → 10%
- `PremiumCustomerDiscountRule` — `customer->isPremium` → 5%
- `DiscountContext` DTO carries `subtotal`, `customer`, and the mutable `accumulatedDiscount` through the pipeline
- `DiscountResult` DTO is the immutable output: `percentage`, `amount`, `total`

### PlaceOrderHandler (`app/Application/Order/PlaceOrderHandler.php`)

Entry point for all order creation. Loads products via a single `whereIn` (no N+1). Calls `DiscountCalculator::calculate()` then persists `Order` + `OrderItem` records via `EloquentOrderRepository` (which wraps everything in `DB::transaction()`). Fires async notification.

### Notification flow

`LaravelMailOrderNotifier` queues an `OrderConfirmation` mailable (stores only the order ID). The queue worker reloads the order from the database via `EloquentOrderRepository::findById()` and renders the Blade template. Jobs are pushed to **Redis** (`QUEUE_CONNECTION=redis`, `phpredis` extension) and consumed by the dedicated `queue` container running `queue:work`.

### HTTP layer

Two routes (`GET /order`, `POST /order`) → `OrderController`. `StoreOrderRequest` handles validation. `GET /products/search` → `ProductController` returns paginated JSON for the live product picker.

### Database

- `customers`: `id`, `name`, `email` (unique), `is_premium`
- `products`: `id`, `name`, `price`
- `orders`: `id`, `customer_id` (nullable), `guest_email` (nullable), `subtotal`, `discount_percentage`, `discount_amount`, `total`, `status`
- `order_items`: `id`, `order_id`, `product_id`, `product_name`, `qty`, `unit_price`
