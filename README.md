# points-mall-shop

> PHP Laravel mall service — manages everything visible in the storefront: products, inventory, exchange orders, and all back-office system configuration.

## Responsibilities

- **Product Catalog** — CRUD for products: title, images, category, points cost, stock quantity, on/off shelf status
- **Amazon Product Sync** — scheduled Artisan command polls ThirdPartyConnector (via BFF) to pull and upsert Amazon product data
- **Exchange Order Lifecycle** — create order (pending → confirmed → fulfilled → cancelled), deduct points via BFF → Core
- **Inventory Control** — atomic stock decrement on order creation; stock recovery on cancellation
- **Dynamic Menu Config** — admin-managed menu tree persisted in DB; BFF queries this to build frontend sidebar
- **System Announcements** — CRUD for global announcements displayed on the employee dashboard
- **Global Feature Flags** — key/value config table for feature toggles consumed by the frontend

## Why This Tech Stack

PHP and Laravel are the dominant stack in e-commerce — Shopify’s ecosystem, WooCommerce, and a large share of enterprise mall systems are built on PHP. Laravel 13 is the framework of choice because it ships with everything an e-commerce backend needs out of the box: Eloquent ORM, Artisan command scheduler, Queues, and a migration system. Using PHP here also demonstrates polyglot backend capability across a single project.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | PHP 8.5, Laravel 13 |
| ORM | Eloquent ORM |
| Database | PostgreSQL 15 |
| Migration | Laravel Migrations |
| Scheduler | Laravel `schedule:run` (product sync, order cleanup) |
| Auth | JWT middleware (validates BFF-issued tokens) |
| Docs | L5-Swagger (OpenAPI) |

## Local Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
# API: http://localhost:8081
```

## Key Environment Variables

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=points_shop
DB_USERNAME=postgres
DB_PASSWORD=your-password
BFF_INTERNAL_SECRET=shared-hmac-secret
```

## Database Ownership

Manages `points_shop` database: products, categories, orders, menu_items, announcements, system_configs.

Schema documented in `.wiki/db/shop-schema.md`.
