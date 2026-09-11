# Store Order & Inventory Mini-System

A small Laravel application designed for a shop counter. The system allows a single operator to manage customers, place orders, maintain product stock, and generate order confirmation records. It includes a web-based UI and a JSON API.

## Features

* Customer management
* Product and stock management
* Order creation
* Automatic stock deduction when an order is placed
* Protection against overselling
* Low-stock product detection
* Stock movement logging
* Order history by customer email
* Order confirmation job using Laravel queues
* PDF invoice generation
* JSON API endpoints
* Automated tests
* Docker support

---

# Requirements

You can run the application in either of the following ways:

1. **Using Docker** — recommended for an easier setup
2. **Using a local PHP/MySQL/Node.js environment**

## Docker Requirements

* Docker
* Docker Compose

## Local Setup Requirements

* PHP 8.3 or higher
* Composer
* MySQL
* Node.js and npm

> **Important:** MySQL is required for the complete test suite. Most tests use an in-memory SQLite database, but the concurrency test requires a real MySQL database.

---

# Running with Docker

Docker is the recommended way to run the application because it provides the required PHP, MySQL, and queue-worker environment without requiring them to be installed separately on the host machine.

## 1. Clone the repository

```bash
git clone <repository-url>
cd Store-Inventory
```

## 2. Create the environment file

```bash
cp .env.example .env
```

> If you are using Windows PowerShell, you can use:

```powershell
Copy-Item .env.example .env
```

## 3. Install Composer dependencies

If the project is being set up without an existing `vendor` directory, install the PHP dependencies first:

```bash
composer install
```

Then generate the application key:

```bash
php artisan key:generate
```

> If you are using the Docker-only workflow and PHP/Composer are not installed on your machine, you can generate the application key inside the container after it starts instead.

## 4. Start the Docker Containers

Before running the following command, make sure **Docker is installed and Docker Desktop is running** on your system.

You can verify that Docker is available by running:

```bash
docker --version
```

Once Docker is running, start the application with:

```bash
docker compose up -d --build
```

This will:

* Build the Laravel application image
* Start the MySQL container
* Run the database migrations and seeders
* Start the Laravel application
* Start the queue worker for processing order confirmation jobs

The application will then be available at:

```text
http://localhost:8000
```


## 5. View application logs

```bash
docker compose logs -f app
```

To view all container logs:

```bash
docker compose logs -f
```

## 6. Stop the application

```bash
docker compose down
```

To stop the containers and remove the database volume:

```bash
docker compose down -v
```

> **Warning:** `docker compose down -v` deletes the MySQL database created by Docker.

---

# Docker Database Configuration

The application container uses the database credentials defined in `docker-compose.yml`.

The application does not rely on the host machine's MySQL credentials when running through Docker.

This keeps the Docker setup predictable and avoids issues caused by special characters or different database credentials in the local `.env` file.

By default, the MySQL port is not exposed to the host machine.

If you want to connect to the Docker MySQL database using a GUI tool such as MySQL Workbench or DBeaver, uncomment the `ports` configuration under the `db` service in `docker-compose.yml`.

---

# Running Without Docker

If you prefer to run the application directly on your machine, make sure PHP, Composer, MySQL, Node.js, and npm are installed.

## 1. Clone the repository

```bash
git clone <repository-url>
cd Store-Inventory
```

## 2. Install PHP dependencies

This step is required before running any `php artisan` command because Laravel needs the Composer-generated `vendor/autoload.php` file.

```bash
composer install
```

## 3. Create the environment file

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

## 4. Generate the Laravel application key

```bash
php artisan key:generate
```

## 5. Configure MySQL

Open the `.env` file and configure your local MySQL database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store_inventory
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database in MySQL before running the migrations.

For example:

```sql
CREATE DATABASE store_inventory;
```

## 6. Run migrations and seed the database

```bash
php artisan migrate --seed
```

## 7. Install frontend dependencies

```bash
npm install
```

## 8. Build frontend assets

```bash
npm run build
```

## 9. Start the Laravel application

```bash
php artisan serve
```

The application will be available at:

```text
http://127.0.0.1:8000
```

---

# Queue Worker

When an order is placed, the application dispatches a queued job to create the order confirmation email/log entry.

When running the application locally, start the queue worker in a separate terminal:

```bash
php artisan queue:work
```

Alternatively, you can use Laravel's development command:

```bash
composer run dev
```

This starts the application server, queue worker, log output, and Vite development server together.

---

# Running the Tests

Run the test suite with:

```bash
php artisan test
```

Most tests use an in-memory SQLite database because it is fast and suitable for most application-level tests.

## Concurrency Test

The following test is different:

```text
tests/Feature/OrderConcurrencyTest.php
```

This test verifies that two simultaneous order requests cannot purchase the same last item in stock.

The test requires:

* A real MySQL database
* Two separate database connections
* The PHP `pcntl` extension for process forking

SQLite is not suitable for accurately simulating this database concurrency scenario.

If MySQL is unavailable or the `pcntl` extension is not installed, the concurrency test is skipped while the remaining tests continue to run.

---

# API Endpoints

| Method | Endpoint                             | Description                                                |
| ------ | ------------------------------------ | ---------------------------------------------------------- |
| POST   | `/api/orders`                        | Creates a new order                                        |
| GET    | `/api/orders/history?email=`         | Returns a customer's order history                         |
| GET    | `/api/products/low-stock?threshold=` | Returns products at or below the specified stock threshold |

## Create Order

```http
POST /api/orders
```

The request can contain either an existing `customer_id` or a customer's name and email.

Example structure:

```json
{
    "customer_id": 1,
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ],
    "amount_paid": 1000
}
```

## Order History

```http
GET /api/orders/history?email=customer@example.com
```

If the email does not exist, the API returns an empty order list.

## Low Stock Products

```http
GET /api/products/low-stock
```

A custom threshold can also be provided:

```http
GET /api/products/low-stock?threshold=5
```

If no threshold is provided, the application uses the `LOW_STOCK_THRESHOLD` environment value. The default value is `10`.

---

# Web Routes

The application also provides web-based pages for the shop counter.

Main pages include:

```text
/orders
/products
```

The web interface and API use the same underlying order and stock-handling logic to ensure that stock validation behaves consistently regardless of how an order is created.

---

# Prompt Log and Demo Video

AI-assisted development was used during the development process.

AI prompt screenshots are available in:

```text
/prompts
```

Screen recording walkthrough:

```text
https://drive.google.com/file/d/1jqfGmS2Yjia9WgOesIs8T8CXgOdH1Tj0/view?usp=sharing
```

---

# Assumptions and Design Decisions

## Single Operator

The application is designed as a small shop-counter system operated by a single person.

Therefore, authentication, user accounts, permissions, and roles were intentionally not added.

If the application were expanded into a multi-user system, Laravel Breeze or Fortify could be introduced later.

## Product Management

Products can be added through the application, but editing and deleting products are intentionally not provided through the UI.

The main purpose of the application is order processing and reliable stock management rather than complete product CRUD.

Allowing users to modify historical product prices or stock directly would ideally require an audit trail, so I kept this functionality outside the scope of the mini-project.

## Payment

The application assumes that customers pay the full amount at checkout.

`amount_paid` is required for each order, and the order summary displays the change due when applicable.

Partial payments, credit sales, and outstanding balances are outside the scope of this project.

## Customer Matching

Customers are identified by email.

When an order is created using a customer's name and email, the application checks whether the email already belongs to an existing customer.

If a matching customer exists, that customer record is reused instead of creating a duplicate.

---

# Preventing Overselling

Stock validation is performed inside a database transaction.

The relevant product rows are locked before the application checks and updates stock quantities.

Products are locked in a consistent ID order to reduce the possibility of deadlocks when multiple orders contain overlapping products.

If two orders attempt to purchase the same limited stock at the same time, one transaction obtains the lock first and updates the stock. The other transaction then sees the updated quantity and fails cleanly if insufficient stock remains.

Deadlock handling is also included so a transaction can be retried when the database reports a deadlock.

If the same product appears multiple times in a single order, its quantities are combined before stock validation. This prevents a customer from bypassing the stock check by submitting the same product multiple times in one request.

---

# Stock Movement Logging

The application records stock movements whenever stock changes.

Although a stock movement log was not explicitly required, it provides a clear audit trail for stock changes and makes the overselling logic easier to test and understand.

It also provides a foundation for future features such as:

* Stock restocking
* Manual stock adjustments
* Stock history
* Inventory auditing

---

# Order Pricing and Tax

Each product has its own tax percentage.

When an order is created, the product's current unit price and tax percentage are copied to the corresponding `order_items` record.

This ensures that historical orders remain accurate even if the product's price or tax rate changes later.

The generated invoice therefore reflects the values that were applicable at the time the order was placed.

---

# Order Numbers

Orders use short, human-readable random identifiers such as:

```text
ord48213
```

instead of exposing the database auto-increment ID.

This makes order numbers easier to read and prevents customers from easily estimating the total number of orders from the order number.

A unique database constraint and retry mechanism are used to handle the unlikely possibility of a generated order-number collision.

---

# PDF Invoice

PDF invoices are generated using:

```text
barryvdh/laravel-dompdf
```

I selected this package instead of a solution such as `wkhtmltopdf` because it is PHP-based and does not require an additional external binary.

This keeps the project simpler to configure and deploy, particularly in a Docker environment.

---

# API Response Design

The API responses are formatted using small private formatting methods inside the controllers rather than separate Laravel API Resource classes.

For a small API with a limited number of response structures, keeping the formatting close to the controller makes the response format easy to understand and maintain.

If the API grows significantly, dedicated API Resource classes could be introduced.

---

# Order Confirmation

When an order is successfully created, a queued job named:

```text
SendOrderConfirmationEmail
```

is dispatched after the database transaction has successfully committed.

There is no external mail server configured for this project.

Therefore, the confirmation email is represented by a log entry rather than an actual email being sent.

Dispatching the job only after the transaction commits ensures that a failed order does not trigger a confirmation notification.

---

# Technology Stack

* **Backend:** Laravel / PHP
* **Database:** MySQL
* **Frontend:** Blade, JavaScript, CSS
* **Build Tool:** Vite
* **Queue:** Laravel Queue
* **PDF:** DomPDF
* **Testing:** PHPUnit / Laravel Test Suite
* **Containerization:** Docker / Docker Compose
* **API:** REST-style JSON API

---

# Project Setup Troubleshooting

## `vendor/autoload.php` not found

If you see:

```text
Failed to open stream: No such file or directory
vendor/autoload.php
```

install the Composer dependencies:

```bash
composer install
```

Then run:

```bash
php artisan key:generate
```

## `.env` file does not exist

Create it from the example file:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Then generate the application key:

```bash
php artisan key:generate
```

## Database connection error

Check the following values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=store_inventory
DB_USERNAME=root
DB_PASSWORD=your_password
```

Make sure MySQL is running and that the database exists.

## Frontend assets are missing

Run:

```bash
npm install
npm run build
```

For development with Vite:

```bash
npm run dev
```

---

# Project Structure

The main application components are organized using Laravel's standard structure:

```text
app/
├── Http/
│   └── Controllers/
├── Jobs/
├── Models/
└── ...

database/
├── migrations/
├── seeders/
└── ...

resources/
├── views/
└── ...

routes/
├── api.php
└── web.php

tests/
├── Feature/
└── Unit/
```

---

# Conclusion

This project was designed to demonstrate a practical Laravel implementation for a small shop-counter workflow, with particular attention to reliable stock management, transactional order processing, API design, queued jobs, testing, and maintainability.

The implementation intentionally avoids unnecessary complexity while providing a foundation that could be extended with authentication, roles, product CRUD, payment integrations, real email delivery, reporting, and advanced inventory management in a larger production system.
