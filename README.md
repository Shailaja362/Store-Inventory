# Store Order & Inventory Mini-System

A small Laravel app for a shop counter. It takes orders, keeps stock in sync, and logs a confirmation "email" for each order. There's a basic web UI and a JSON API.
## Running it with Docker

You can skip installing PHP, MySQL, and Node yourself and use Docker instead.

First, still generate an app key locally:

```bash
cp .env.example .env
php artisan key:generate
```

Then start everything:

```bash
docker compose up -d --build
```

This will:

- Build the app image
- Start a MySQL container
- Run migrations automatically
- Start the app at [http://localhost:8000](http://localhost:8000)
- Start a queue worker so confirmation emails get processed

A few notes:

- The app container does not use the DB username/password from your `.env`. It uses its own fixed values set in `docker-compose.yml`. This avoids issues with special characters in passwords.
- The MySQL port is not exposed to your machine by default. If you want to connect with a GUI tool, uncomment the `ports` line under `db` in `docker-compose.yml`.
- To see logs: `docker compose logs -f app`
- To stop everything and delete the database: `docker compose down -v`

## What you need for without docker setup

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL

MySQL is needed, not just SQLite. One test checks that stock can't be oversold, and that needs a real second database connection. SQLite can't do that.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Open `.env` and set your database details:

```
DB_CONNECTION=mysql
DB_DATABASE=store_inventory
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

The app creates a queued job when an order is placed (the confirmation email). Run a worker in another terminal so it actually gets processed:

```bash
php artisan queue:work
```

Or just run everything at once:

```bash
composer run dev
```

That starts the server, the queue worker, log output, and Vite together.


## Running the tests

```bash
php artisan test
```

Most tests use an in-memory SQLite database. That's fast and works fine for almost everything.

One test is different: `tests/Feature/OrderConcurrencyTest.php`. It checks that two people can't buy the last item in stock at the same time. To test that properly, it needs two real, separate database connections racing each other. SQLite can't simulate that, so this test connects to a real MySQL database and runs two order attempts in parallel using forked processes.

If MySQL isn't available, or the `pcntl` PHP extension isn't installed, this one test is skipped. Everything else still runs normally.

## API endpoints

| Method | Endpoint | What it does |
|---|---|---|
| POST | `/api/orders` | Creates an order. Send `customer_id` (or `customer_email` + `customer_name`), a list of `items` (`product_id`, `quantity`), and `amount_paid`. |
| GET | `/api/orders/history?email=` | Returns a customer's past orders by email. Returns an empty list if the email is unknown. |
| GET | `/api/products/low-stock?threshold=` | Returns products at or below the low-stock threshold. If `threshold` is left out, it uses `LOW_STOCK_THRESHOLD` from `.env` (default is 10). |

The web pages (`/orders`, `/products`) use their own routes, not this API. They share the same order logic underneath, so stock handling works the same either way.

## Prompt log & demo video

- AI prompt screenshots: [`/prompts`](./prompts)
- Screen recording walkthrough: _add link here_

## Assumptions & Design Decisions

A few things in the brief weren't fully spelled out, so here's what I assumed and why, plus a couple of technical calls worth explaining.

**Single operator, no login.** I read this as a tool for whoever's behind the counter, not a multi-user back office, so there's no authentication or roles — every page and endpoint is open. If this grew into something more people needed accounts for, Breeze/Fortify would slot in without much rework.

**Products can be added but not edited or deleted from the UI.** The brief is really about placing orders and keeping stock honest, not full product CRUD. Letting someone silently edit a price or stock count outside of an order felt like it needed its own audit trail rather than being bolted on quickly, so I left it out instead of doing it half right.

**Payment is full and upfront.** `amount_paid` is required on every order and the bill preview shows change due — I assumed a walk-in counter sale, paid in full at checkout, not partial payments or invoicing on credit. There's no balance-due tracking beyond a single order.

**Customers are matched by email.** The order form takes either a `customer_id` or a name + email. If that email already belongs to someone, the existing customer record is reused instead of creating a duplicate. Since there's no login, email seemed like the only reasonable natural key for a walk-in customer.

On the implementation side, a few decisions are worth flagging:

Overselling is prevented by locking the relevant product rows inside a database transaction before checking stock, and locking them in a fixed order (sorted by ID) so two orders sharing products don't deadlock each other. Whoever gets there first sees the real number; the other request either fails cleanly against the now-updated stock or gets retried automatically if it hits a deadlock. If the same product shows up twice in one order, the quantities are added together first — otherwise someone could split one big request into smaller ones to dodge the stock check.

I also added a stock movement log even though it wasn't explicitly asked for — it made testing the no-overselling rule much easier to reason about, and it gives a natural place to hang restocking/adjustments later without touching the schema again.

Tax is captured per order line rather than looked up live: each product has its own `tax_percentage`, and that rate (along with the unit price) gets copied onto the `order_items` row at the time of purchase. That way a past order — and its PDF — stays accurate even if the product's price or tax rate changes afterward.

Order numbers are short random codes like `ord48213` rather than the raw auto-increment ID, mostly so the shop's order volume isn't obvious from the number and it's short enough to read off a printed invoice. A unique constraint plus a small retry loop handles the rare collision.

For the PDF invoices I went with `barryvdh/laravel-dompdf` over something like `wkhtmltopdf` since it's pure PHP with no external binary to install — one less moving part for a project this size, and one less thing to configure in Docker.

API responses are built with small private formatting methods on the controllers rather than Resource classes — for an API this small it was easier to read the exact response shape right where it's returned. And since there's no real mail server configured, the "confirmation email" is just a log entry written by `SendOrderConfirmationEmail`, dispatched only after the order transaction commits so it never fires for an order that failed.
