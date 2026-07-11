#!/bin/sh
# Run on every container start (Render/Docker): migrations are idempotent (Laravel
# tracks applied migrations in the migrations table), and MenuItemSeeder is
# idempotent by design (keyed by label+parent_id, see its docblock) — safe to
# re-run on every deploy so a fresh production database is never left with an
# empty/missing menu_items table.
set -e

php artisan migrate --force
php artisan db:seed --force --class=MenuItemSeeder

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8081}"
