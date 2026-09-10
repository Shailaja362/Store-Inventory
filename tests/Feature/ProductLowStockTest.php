<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_products_at_or_below_the_default_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        $low = Product::factory()->create(['stock_quantity' => 5]);
        $borderline = Product::factory()->create(['stock_quantity' => 10]);
        $healthy = Product::factory()->create(['stock_quantity' => 50]);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($low->id));
        $this->assertTrue($ids->contains($borderline->id));
        $this->assertFalse($ids->contains($healthy->id));
    }

    public function test_it_accepts_a_configurable_threshold_query_parameter(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 25]);

        $response = $this->getJson('/api/products/low-stock?threshold=30');

        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->pluck('id')->contains($product->id));
    }
}
