# Prizm agent guide

Prizm is a flat PHP storefront from a 2017 SJSU class project (see the README for the history). Keep that shape. Do not introduce a framework, a SPA, or a payment provider.

## Architecture

Requests hit a page in the repo root (`index.php`, `allproducts.php`, `cart.php`, ...). Those pages call helpers and print HTML.

- `lib/bootstrap.php` loads `.env` without overriding variables that are already set, opens PDO, creates tables, and seeds the sample catalog once.
- `lib/catalog.php` is the data and session layer: products, login, cart, orders, reviews, peer JSON.
- `lib/html.php` is the markup from the original `util.php` (`createProductUI`, `buildProductsUI`, `buildCartUI`, `buildRatingStars`, `getJson`). These functions return strings. They do not echo.
- `util.php` and `config.php` only load those files, so older `include` lines still work.
- `router.php` is the front door for `php -S`. It refuses `data/`, `lib/`, `tests/`, `.env`, the SQLite file, and other non-public paths. Apache gets the same idea from `.htaccess`.

Default database is SQLite at `data/prizm.sqlite`. `PRIZM_DB_DRIVER=mysql` uses the `PRIZM_DB_*` variables. The original table and column names are preserved, including the inconsistent casing `orders.userid` versus `ratings.userId`.

Partner catalogs are not stored. `buildAllProducts()` GETs each `PRIZM_PEER_URLS` entry, expects the `exposeproducts.php` JSON shape, and renders cards that do not link into the local product ids and do not add to the cart.

## Conventions

- PHP 8.3+, `declare(strict_types=1)`, PSR-12. `composer lint` runs PHP CS Fixer. `composer format` writes the fixes.
- Queries go through PDO prepared statements. Do not concatenate ids, review text, or cart contents into SQL.
- Escape HTML with `prizm_h()`. Image URLs go through `prizm_safe_url()` so `javascript:` sources are dropped.
- Passwords use `password_hash()` / `password_verify()`.
- State-changing POSTs check `prizm_csrf_ok()`. The token is in the `<meta name="csrf-token">` tag and in hidden form fields. `js/custom.js` sends it when adding to the cart.
- New sample data belongs in `prizm_seed()`, and only runs when the products table is empty and `PRIZM_SEED` is not `0`.
- Do not add a license file. That choice is the owner's.
- Do not commit `.env`, `vendor/`, or `data/*.sqlite`.

## Commands

```bash
composer install
composer test          # PHPUnit, in-memory SQLite unless PRIZM_DB_DRIVER is set
composer lint
composer format
composer serve         # http://127.0.0.1:8080 via router.php
php bin/init-db.php    # open the DB and print the product count
```

CI (`.github/workflows/ci.yml`) runs lint and tests on PHP 8.3 and 8.4 with SQLite, and the tests again on PHP 8.3 with MySQL 8.4. The MySQL root password in that workflow (`prizm`) exists only for CI.

Sample login after a seeded local database: `demo@prizm.test` / `demo`. Tests turn seeding off.

## Gotchas

- The `mysql_*` functions were removed in PHP 7. This codebase used them everywhere. Do not bring them back. A test fails if a PHP file calls `mysql_query`, `mysql_connect`, and the related functions.
- `createProductUI()` used to echo and return null, while callers concatenated the return value. Output happened as a side effect. Callers now echo the returned string. Keep it that way.
- The cart used to be a session string like `'1','2'` interpolated into `IN (...)`. It is now a list of integers. `prizm_normalize_ids()` still accepts that old quoted string. Adding the same id twice does not create a second line, matching the old `IN` query, which also returned each id once. There is no quantity column.
- `getJson()` used to link to `displayproducts.php`. The real page is `displayproduct.php`.
- `addtocart.php` answers in plain text (`Product added to cart`, `Please log in`, `Product not found`). Leave those strings; the UI shows them as-is.
- `newreview.php` answers `Please log in to add reviews` and `New review added ` (trailing space included) when it is not redirecting back to the product.
- Reviews and the cart require a logged-in session. The original login form was never committed, so `users` is a new table. Checkout is the same story: the button existed and went nowhere, and PayPal was never integrated. Checkout now inserts `orders` and `ordermap` and says so on the page. Do not bolt on a fake PayPal call.
- `starrating.html` contains PHP but will not run, because it is HTML. `buildRatingStars()` is the widget. `starrating.php` is a preview.
- `buildAllProducts()` no longer disables TLS verification and no longer calls `https://localhost/valarshopoholics/exposeproducts.php`. An empty `PRIZM_PEER_URLS` means the catalog is local only. A bad peer URL is skipped; it must not take the page down.
- Seeding uses fixed ids (user 1, order 1, products 1–6). The recommendation URL `user_recommend.php?uid=1` depends on that.
- `prizm_load_env()` will not overwrite an existing environment variable. Tests rely on that. Set `PRIZM_DB_PATH=:memory:` and `PRIZM_SEED=0` before the first `prizm_db()` call, and call `prizm_reset()` between cases.
- The SQLite file is created next to the code. Serving the repo with Apache or `php -S` **without** `router.php` would let someone download it. Use `composer serve`.
