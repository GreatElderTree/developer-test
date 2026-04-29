# Order Management System

A Laravel 11 application refactored from a single-file PHP script into a layered, tested, production-ready order management system.

---

## The starting point

The assignment provided this script as the baseline:

```php
class OrderController
{
    public function store($request)
    {
        $db = new PDO(...);

        $customer = $db->query("SELECT * FROM customers WHERE id = ".$request['customer_id'])->fetch();

        if (!$customer) {
            die("Customer not found");
        }

        $total = 0;

        foreach ($request['items'] as $item) {
            $product = $db->query("SELECT * FROM products WHERE id = ".$item['product_id'])->fetch();
            $total += $product['price'] * $item['qty'];
        }

        $db->exec("INSERT INTO orders(total) VALUES($total)");

        mail($customer['email'], "Order Confirmed", "Thanks!");

        echo json_encode(["success" => true]);
    }
}
```

**Problems with the original:**

| Problem | Impact |
|---|---|
| Raw string concatenation in SQL queries | SQL injection on every field |
| `die()` for error handling | Crashes the process with a plain text response |
| N+1 queries — one `SELECT` per order line | Degrades linearly with cart size |
| `mail()` called inline | HTTP response blocked until SMTP handshake completes |
| No validation | Any payload hits the database |
| No transaction | If the item inserts fail, an orphan `orders` row remains |
| `INSERT` stores only a bare total | No line item history, no discount breakdown |
| Single-method controller doing everything | Impossible to test, extend, or reuse |
| Float arithmetic for money | IEEE 754 rounding errors accumulate silently |

---

## What was built

### Required features

- **Architecture** — Domain / Application / Infrastructure / HTTP layers with dependency inversion throughout
- **Performance** — Single `whereIn` to load all products in one query; bulk `INSERT` for all order items; no N+1 anywhere
- **Console command** — `php artisan customer:create` with interactive prompts and inline validation
- **Guest + customer orders** — email lookup resolves registered customers automatically; unknowns become guests
- **Discount pipeline** — rules applied in sequence, capped at 20%, extensible without touching existing code
  - Subtotal > €100 → 10%
  - Premium customer → +5%
- **Tests** — 40 tests across unit and feature layers
- **Order form** — live product search, real-time discount preview, full validation feedback
- **README** — this document

### Additional improvements beyond the brief

| Area | What changed |
|---|---|
| SQL injection | Eliminated — Eloquent parameterised queries throughout |
| Money representation | Integer cents (`5000` = €50.00) — no float drift, no library needed |
| Email delivery | Queued mailable via Redis — HTTP response returns immediately |
| Validation | `StoreOrderRequest` rejects missing fields, invalid products, zero qty, >1000 qty, duplicate product IDs, and empty carts |
| Database integrity | `DB::transaction()` wraps order + item inserts — all-or-nothing |
| Order history | `order_items` table snapshots `product_name` and `unit_price` at order time — catalogue changes don't corrupt history |
| Dependency injection | All infrastructure dependencies injected via constructor; no `new` inside business logic |
| Async notifications | `OrderConfirmation` mailable queued to Redis; worker reloads the full order from DB to render the template |
| `OrderConfirmation` | Constructor-injected `OrderRepositoryInterface` — no `app()` service locator, fully testable in isolation |
| Live product picker | Paginated AJAX search with debounce — no upfront product dump |
| Guest support | `customer_id` nullable; `guest_email` captured on the order |

---

## Setup

**Requirements:** Docker & Docker Compose, Node.js 20+ (build only)

```bash
# 1. Clone
git clone <repo-url> && cd crewplanner

# 2. Environment
cp .env.example .env

# 3. Build frontend assets
npm ci && npm run build

# 4. Start containers
docker compose up -d --build

# 5. App key + database
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

The application is available at **http://localhost:8080**.

To rebuild CSS/JS after editing `resources/`:

```bash
npm run build
```

---

## Usage

### Placing an order

Visit [http://localhost:8080/order](http://localhost:8080/order).

- Enter an email address. Registered customers are resolved automatically; unknown emails become guests.
- Search for products by name and add quantities. The order summary updates live with the estimated discount.
- Submit. A confirmation email is queued and delivered asynchronously.

### Creating a customer

```bash
docker compose exec app php artisan customer:create
```

Interactive prompts ask for name, email, and premium status. Duplicate email addresses are rejected with a re-prompt.

---

## Running tests

```bash
# Full suite (40 tests, SQLite in-memory — no extra setup)
docker compose exec app php artisan test

# Single file
docker compose exec app php artisan test tests/Feature/PlaceOrderHandlerTest.php

# Single test by name
docker compose exec app php artisan test --filter=test_applies_premium_discount_on_top
```

---

## Discount rules

| Condition | Discount |
|---|---|
| Order subtotal > €100 | 10% |
| Customer is premium | +5% |
| **Maximum total** | **20%** |

Rules run in pipeline order. Each returns an additional percentage; `DiscountCalculator` accumulates and caps at 20%.

**Adding a new rule** — implement `DiscountRuleInterface` and register it in `AppServiceProvider`:

```php
$this->app->singleton(DiscountCalculator::class, fn () => new DiscountCalculator([
    new OrderTotalDiscountRule(),
    new PremiumCustomerDiscountRule(),
    new YourNewRule(),          // ← one line
]));
```

No existing class changes required.

---

## Money

All monetary values are stored and passed as **integer cents** (`5000` = €50.00). Division by 100 happens only at display boundaries — Blade templates, flash messages, and the JSON product search response.

This means:
- No IEEE 754 drift — integer arithmetic is exact
- No library dependency
- No implicit type coercions — `int` parameters catch mistakes at the type level
- A single explicit `(int) round()` in `DiscountResult` is the only rounding site in the entire codebase

The database stores monetary columns as `unsignedInteger` (cents). `discount_percentage` stays `decimal(5,2)` because it is a ratio, not an amount.

---

## Architecture

```
app/
├── Application/
│   ├── Customer/
│   │   ├── CreateCustomerCommand.php       # Input DTO for customer creation
│   │   └── CreateCustomerHandler.php       # Duplicate-email check + persist
│   └── Order/
│       ├── PlaceOrderCommand.php           # Input DTO for order creation
│       └── PlaceOrderHandler.php           # Orchestrates product load, discount, persist, notify
│
├── Domain/
│   ├── Customer/
│   │   ├── Customer.php                    # Pure domain object
│   │   └── Ports/CustomerRepositoryInterface.php
│   ├── Discount/
│   │   ├── DiscountCalculator.php          # Runs the rule pipeline, enforces 20% cap
│   │   ├── DiscountContext.php             # Immutable context passed through the pipeline
│   │   ├── DiscountResult.php              # Immutable result: percentage, amount (cents), total (cents)
│   │   └── Rules/
│   │       ├── DiscountRuleInterface.php
│   │       ├── OrderTotalDiscountRule.php  # Subtotal > 10000 cents → 10%
│   │       └── PremiumCustomerDiscountRule.php
│   ├── Order/
│   │   ├── Order.php
│   │   ├── OrderItem.php                   # Snapshots product name + unit price at order time
│   │   └── Ports/
│   │       ├── OrderNotifierInterface.php
│   │       └── OrderRepositoryInterface.php
│   └── Product/
│       ├── Product.php
│       ├── ProductSearchResult.php
│       └── Ports/ProductRepositoryInterface.php
│
├── Infrastructure/
│   ├── Notification/
│   │   └── LaravelMailOrderNotifier.php    # Queues OrderConfirmation to Redis
│   └── Persistence/
│       ├── EloquentCustomerRepository.php
│       ├── EloquentOrderRepository.php     # Bulk INSERT for items; wraps in DB::transaction()
│       ├── EloquentProductRepository.php   # Single whereIn — no N+1
│       └── Models/                         # Eloquent models with integer casts for money
│
├── Http/
│   ├── Controllers/
│   │   ├── OrderController.php
│   │   └── ProductController.php          # Paginated JSON search for the live picker
│   └── Requests/StoreOrderRequest.php
│
├── Console/Commands/CreateCustomer.php
├── Mail/OrderConfirmation.php             # Queued mailable; OrderRepositoryInterface injected
└── Providers/AppServiceProvider.php       # Wires ports to adapters; registers discount pipeline
```

### Key design decisions

**Ports and adapters.** Every infrastructure concern (persistence, mail, queuing) is hidden behind an interface defined in the domain. Tests bind lightweight fakes; production binds Eloquent/Redis without touching business logic.

**PlaceOrderHandler.** Single entry point for all order creation. Loads products with one `whereIn`, accumulates the subtotal in a loop, delegates discount calculation to `DiscountCalculator`, then persists via the repository (which wraps everything in `DB::transaction()`).

**Discount pipeline.** `DiscountCalculator` holds an ordered array of `DiscountRuleInterface` implementations. Each rule receives the immutable `DiscountContext` and returns an additional float percentage. The calculator accumulates and enforces the 20% cap. Adding a rule is one line in `AppServiceProvider`.

**Async email.** `LaravelMailOrderNotifier` queues an `OrderConfirmation` mailable that stores only the order ID. The queue worker (`queue` container) reloads the full order via `EloquentOrderRepository::findById()` and renders the Blade template. A failed enqueue is logged but does not roll back the order — email is best-effort.

**Integer cents.** Prices, subtotals, discount amounts, and totals are `int` throughout the domain and database. The only rounding site is `DiscountResult`: `(int) round($subtotal * (float) $percentage / 100)`.

---

## Docker services

| Service | Description | Port |
|---|---|---|
| `app` | PHP 8.4-FPM | internal |
| `webserver` | Nginx | **8080** |
| `db` | MySQL 8.0 | 3306 |
| `redis` | Redis 7 (queue + cache) | 6379 |
| `queue` | Laravel queue worker | internal |
