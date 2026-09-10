<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;
use Throwable;

/**
 * These tests exercise the real MySQL connection (not the sqlite connection
 * used by the rest of the suite) because proving the stock deduction is
 * race-safe requires genuine row locking between two independent database
 * sessions, which an in-memory sqlite connection cannot provide.
 *
 * Two techniques are used:
 *  - test_lockforupdate_blocks_a_second_connection_until_the_first_commits
 *    proves the underlying mechanism: a `lockForUpdate` row lock held by one
 *    connection makes a second, independent connection's lock attempt fail
 *    fast (via a short lock-wait timeout) instead of reading stale data.
 *  - test_two_concurrent_order_attempts_never_oversell_the_last_unit runs
 *    the real OrderService from two forked OS processes racing for the same
 *    single unit of stock, and asserts exactly one succeeds.
 */
class OrderConcurrencyTest extends TestCase
{
    private const CONNECTION = 'mysql_concurrency_test';

    private ?int $productId = null;

    private array $customerIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.'.self::CONNECTION => array_merge(
                config('database.connections.mysql'),
                ['database' => env('DB_DATABASE_CONCURRENCY_TEST', 'store_inventory')],
            ),
        ]);

        try {
            DB::connection(self::CONNECTION)->getPdo();
        } catch (Throwable $exception) {
            $this->markTestSkipped('A real MySQL connection is required for concurrency tests: '.$exception->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $connection = DB::connection(self::CONNECTION);

        if ($this->productId) {
            $connection->table('stock_movements')->where('product_id', $this->productId)->delete();
            $connection->table('order_items')->where('product_id', $this->productId)->delete();
            $connection->table('products')->where('id', $this->productId)->delete();
        }

        if ($this->customerIds !== []) {
            $connection->table('orders')->whereIn('customer_id', $this->customerIds)->delete();
            $connection->table('customers')->whereIn('id', $this->customerIds)->delete();
        }

        DB::purge(self::CONNECTION);

        parent::tearDown();
    }

    public function test_lockforupdate_blocks_a_second_connection_until_the_first_commits(): void
    {
        $this->productId = $this->seedProduct(stock: 1);

        $connectionA = self::CONNECTION.'_a';
        $connectionB = self::CONNECTION.'_b';
        config([
            'database.connections.'.$connectionA => config('database.connections.'.self::CONNECTION),
            'database.connections.'.$connectionB => config('database.connections.'.self::CONNECTION),
        ]);

        DB::connection($connectionA)->beginTransaction();
        DB::connection($connectionA)->table('products')->where('id', $this->productId)->lockForUpdate()->first();

        DB::connection($connectionB)->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $secondConnectionWasBlocked = false;

        try {
            DB::connection($connectionB)->beginTransaction();
            DB::connection($connectionB)->table('products')->where('id', $this->productId)->lockForUpdate()->first();
            DB::connection($connectionB)->commit();
        } catch (PDOException) {
            $secondConnectionWasBlocked = true;
            DB::connection($connectionB)->rollBack();
        }

        DB::connection($connectionA)->table('products')->where('id', $this->productId)->update(['stock_quantity' => 0]);
        DB::connection($connectionA)->commit();

        $this->assertTrue(
            $secondConnectionWasBlocked,
            'A second connection was able to lock a row already locked by an uncommitted transaction — stock could be oversold.',
        );

        $freshStock = DB::connection($connectionB)->table('products')->where('id', $this->productId)->value('stock_quantity');
        $this->assertSame(0, (int) $freshStock);

        DB::purge($connectionA);
        DB::purge($connectionB);
    }

    public function test_two_concurrent_order_attempts_never_oversell_the_last_unit(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension is required to simulate concurrent processes.');
        }

        $this->productId = $this->seedProduct(stock: 1);
        $emailA = 'racer-a-'.uniqid().'@example.com';
        $emailB = 'racer-b-'.uniqid().'@example.com';

        $resultFileA = tempnam(sys_get_temp_dir(), 'order_race_a_');
        $resultFileB = tempnam(sys_get_temp_dir(), 'order_race_b_');

        $pidA = pcntl_fork();

        if ($pidA === 0) {
            $this->attemptOrderInChildProcess($emailA, $resultFileA);
        }

        $pidB = pcntl_fork();

        if ($pidB === 0) {
            $this->attemptOrderInChildProcess($emailB, $resultFileB);
        }

        pcntl_waitpid($pidA, $status);
        pcntl_waitpid($pidB, $status);

        $resultA = json_decode(file_get_contents($resultFileA), true);
        $resultB = json_decode(file_get_contents($resultFileB), true);
        @unlink($resultFileA);
        @unlink($resultFileB);

        $statuses = [$resultA['status'], $resultB['status']];
        sort($statuses);

        $this->assertSame(
            ['insufficient_stock', 'success'],
            $statuses,
            'Expected exactly one of the two concurrent order attempts to succeed and the other to fail cleanly. Got: '.json_encode([$resultA, $resultB]),
        );

        $finalStock = DB::connection(self::CONNECTION)->table('products')->where('id', $this->productId)->value('stock_quantity');
        $this->assertSame(0, (int) $finalStock, 'Stock must never go negative or be double-decremented.');

        $orderCount = DB::connection(self::CONNECTION)->table('order_items')->where('product_id', $this->productId)->count();
        $this->assertSame(1, $orderCount, 'Exactly one order line should have been created for the single unit of stock.');

        foreach ([$emailA, $emailB] as $email) {
            $customerId = DB::connection(self::CONNECTION)->table('customers')->where('email', $email)->value('id');

            if ($customerId) {
                $this->customerIds[] = $customerId;
            }
        }
    }

    private function attemptOrderInChildProcess(string $email, string $resultFile): void
    {
        DB::purge(self::CONNECTION);
        config(['database.default' => self::CONNECTION]);

        try {
            $order = app(OrderService::class)->createOrder([
                'customer_email' => $email,
                'customer_name' => 'Race Condition Tester',
                'items' => [
                    ['product_id' => $this->productId, 'quantity' => 1],
                ],
            ]);

            file_put_contents($resultFile, json_encode(['status' => 'success', 'order_id' => $order->id]));
        } catch (InsufficientStockException) {
            file_put_contents($resultFile, json_encode(['status' => 'insufficient_stock']));
        } catch (Throwable $exception) {
            file_put_contents($resultFile, json_encode(['status' => 'error', 'message' => $exception->getMessage()]));
        }

        exit(0);
    }

    private function seedProduct(int $stock): int
    {
        return DB::connection(self::CONNECTION)->table('products')->insertGetId([
            'name' => 'Concurrency Test Product',
            'code' => 'RACE-'.uniqid(),
            'price' => 10,
            'tax_percentage' => 0,
            'stock_quantity' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
