<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_for_a_new_customer_and_deducts_stock(): void
    {
        Bus::fake();

        $product = Product::factory()->create([
            'price' => 100,
            'tax_percentage' => 10,
            'stock_quantity' => 20,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'new.customer@example.com',
            'customer_name' => 'New Customer',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.subtotal', '300.00');
        $response->assertJsonPath('data.tax_total', '30.00');
        $response->assertJsonPath('data.grand_total', '330.00');

        $this->assertDatabaseHas('customers', [
            'email' => 'new.customer@example.com',
            'name' => 'New Customer',
        ]);

        $this->assertDatabaseHas('orders', [
            'subtotal' => 300,
            'tax_total' => 30,
            'grand_total' => 330,
        ]);

        $this->assertSame(17, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity_change' => -3,
            'previous_stock' => 20,
            'new_stock' => 17,
        ]);

        Bus::assertDispatched(SendOrderConfirmationEmail::class);
    }

    public function test_it_reuses_an_existing_customer_by_email(): void
    {
        Bus::fake();

        $customer = Customer::factory()->create(['email' => 'existing@example.com']);
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->postJson('/api/orders', [
            'customer_email' => 'existing@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $this->assertSame(1, Customer::where('email', 'existing@example.com')->count());
        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id]);
    }

    public function test_it_places_an_order_for_a_customer_selected_by_id(): void
    {
        Bus::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.customer.id', $customer->id);
        $this->assertSame(1, Customer::count());
    }

    public function test_it_rejects_an_order_when_requested_quantity_exceeds_stock(): void
    {
        Bus::fake();

        $product = Product::factory()->create(['stock_quantity' => 2]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'shopper@example.com',
            'customer_name' => 'Shopper',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');

        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        Bus::assertNotDispatched(SendOrderConfirmationEmail::class);
    }

    public function test_it_requires_a_name_for_a_new_customer(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->postJson('/api/orders', [
            'customer_email' => 'unknown@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertJsonValidationErrors('customer_name');
    }

    public function test_it_requires_at_least_one_item(): void
    {
        $this->postJson('/api/orders', [
            'customer_email' => 'shopper@example.com',
            'customer_name' => 'Shopper',
            'items' => [],
        ])->assertJsonValidationErrors('items');
    }

    public function test_it_merges_duplicate_product_lines_in_the_same_order(): void
    {
        Bus::fake();

        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 50, 'tax_percentage' => 0]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'shopper@example.com',
            'customer_name' => 'Shopper',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(5, 10 - $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }
}
