# Store Order & Inventory Mini-System

A small Laravel app for a shop counter. It takes orders, keeps stock in sync, and logs a confirmation "email" for each order. There's a basic web UI and a JSON API.

## What you need

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

## Notes on some decisions

**New customer or existing one?**
The order form accepts a `customer_id`, or a name and email. If the email already belongs to a customer, that customer is reused instead of creating a duplicate.

**How stock is protected from overselling.**
When an order is created, the app locks the rows for every product in that order before checking stock, inside one database transaction. Products are locked in a fixed order (sorted by ID) so two orders sharing products don't deadlock each other. Whoever gets the lock first sees the real stock count. The other request either waits and then fails cleanly, or gets retried automatically if a deadlock happens.

**Same product listed twice in one order.**
If a request lists the same product more than once, the quantities are added together before checking stock. This stops someone from splitting one big request into smaller ones to get around the stock check.

**A stock movement history table.**
This wasn't a strict requirement, but it felt necessary. Every stock change is logged with the amount changed and the stock before and after. It also made testing the "no overselling" rule easier.

**`amount_paid` is required.**
The order screen shows change owed to the customer, so this field needs a value to calculate that.

**No API Resource classes.**
Responses are built with small private methods in the controllers instead of Laravel Resource classes. For an API this small, it felt simpler to read the response shape directly where it's returned.

**Confirmation email is just a log entry.**
No real mail server is set up, so `SendOrderConfirmationEmail` logs the order details instead of sending an email. It only runs after the order is fully saved, so it never fires for an order that fails.
