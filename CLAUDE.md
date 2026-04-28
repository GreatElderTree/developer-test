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
docker compose exec app php artisan test tests/Feature/OrderServiceTest.php

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

### Discount pipeline (`app/Services/Discount/`)

The core extensibility point. `DiscountService` holds an ordered array of `DiscountRuleInterface` implementations. Each rule returns an additional percentage; the service accumulates and caps at 20%. Rules are registered in `AppServiceProvider::register()` — adding a new rule is a one-liner there.

- `OrderTotalDiscountRule` — subtotal > 100 → 10%
- `PremiumCustomerDiscountRule` — `customer->is_premium` → 5%
- `OrderDiscountContext` DTO carries `subtotal`, `customer`, and the mutable `accumulatedDiscount` through the pipeline
- `DiscountResult` DTO is the immutable output: `percentage`, `amount`, `total`

### OrderService (`app/Services/OrderService.php`)

Entry point for all order creation. Wraps everything in `DB::transaction()`. Loads products via a single `whereIn` (no N+1). Calls `DiscountService::calculate()` then persists `Order` + `OrderItem` records, dispatches `OrderPlaced` event.

### Event flow

`OrderPlaced` event → `SendOrderConfirmationEmail` listener (`ShouldQueue`) → `OrderConfirmation` Mailable. Jobs are pushed to **Redis** (`QUEUE_CONNECTION=redis`, `phpredis` extension) and consumed by the dedicated `queue` container running `queue:work redis`. The listener is registered in `AppServiceProvider::boot()`.

### HTTP layer

Two routes (`GET /order`, `POST /order`) → `OrderController`. The controller resolves a `Customer` by email (or passes `null` for guests) and delegates to `OrderService`. `StoreOrderRequest` handles validation.

### Database

- `customers`: `id`, `name`, `email` (unique), `is_premium`
- `products`: `id`, `name`, `price`
- `orders`: `id`, `customer_id` (nullable), `guest_email` (nullable), `subtotal`, `discount_percentage`, `discount_amount`, `total`, `status`
- `order_items`: `id`, `order_id`, `product_id`, `qty`, `unit_price`
