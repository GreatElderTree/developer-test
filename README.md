# Order Management System

A Laravel 11 application for managing customer orders, refactored from a single-file PHP script.

## Requirements

- Docker & Docker Compose

PHP, MySQL, and Nginx all run inside containers — no local runtime needed.

## Setup

```bash
# 1. Clone the repository
git clone <repo-url> && cd crewplanner

# 2. Copy environment file
cp .env.example .env

# 3. Build and start containers
docker compose up -d --build

# 4. Generate app key
docker compose exec app php artisan key:generate

# 5. Run migrations and seed sample products
docker compose exec app php artisan migrate --seed
```

The application is now available at **http://localhost:8080**.

## Usage

### Placing an order (web form)

Visit [http://localhost:8080/order](http://localhost:8080/order).

- Enter your email. Existing customers are resolved automatically; unknown emails are treated as guests.
- Search for products and add quantities. The order summary updates live with estimated discounts.
- Submit to place the order. A confirmation email is sent asynchronously via the queue worker.

### Creating a customer via CLI

```bash
docker compose exec app php artisan customer:create
```

Interactive prompts ask for name, email, and whether the customer is premium.

### Running tests

```bash
# All tests (36 tests, SQLite in-memory — no DB setup needed)
docker compose exec app php artisan test

# Single file
docker compose exec app php artisan test tests/Feature/PlaceOrderHandlerTest.php

# Single test by name
docker compose exec app php artisan test --filter=test_applies_premium_discount_on_top
```

## Discount rules

| Condition | Discount |
|---|---|
| Order subtotal > €100 | 10% |
| Customer is premium | +5% |
| **Maximum total** | **20%** |

Rules are applied in sequence and capped at 20%. To add a new rule: implement `App\Domain\Discount\Rules\DiscountRuleInterface` and register it in `AppServiceProvider` — no existing code changes required.

## Architecture

```
app/
  Domain/Discount/Rules/DiscountRuleInterface.php # Interface all discount rules implement
  Domain/Discount/DiscountContext.php             # Mutable context passed through the pipeline
  Domain/Discount/DiscountResult.php              # Immutable result (percentage, amount, total)
  Domain/Discount/DiscountCalculator.php          # Runs the rule pipeline, enforces 20% cap
  Domain/Discount/Rules/                          # One class per discount rule
  Application/Order/PlaceOrderHandler.php         # Orchestrates order creation in a DB transaction
  Infrastructure/Notification/LaravelMailOrderNotifier.php # Queued notifier — non-blocking
  Infrastructure/Persistence/EloquentOrderRepository.php   # Bulk item inserts + findById for queue worker
  Http/Controllers/OrderController.php
  Http/Controllers/ProductController.php          # Paginated product search endpoint
  Http/Requests/StoreOrderRequest.php
  Console/Commands/CreateCustomer.php
```

**Key design decisions:**

- `PlaceOrderHandler` loads all products in a single `whereIn` query — no N+1 reads.
- Order items are persisted in a single bulk `INSERT` — no N+1 writes.
- Confirmation emails store only the order ID in the queue payload; the worker reloads from the DB.
- The discount pipeline is open for extension: new rules register in `AppServiceProvider` without touching existing classes.
- Guest orders store `guest_email` on the order; `customer_id` is nullable.

## Docker services

| Service | Description | Port |
|---|---|---|
| `app` | PHP 8.4-FPM | internal |
| `webserver` | Nginx | **8080** |
| `db` | MySQL 8.0 | 3306 |
| `redis` | Redis 7 (queue + cache) | 6379 |
| `queue` | Laravel queue worker (Redis backend) | internal |
