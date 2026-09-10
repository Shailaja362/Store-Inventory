<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_product(): void
    {
        $response = $this->postJson('/products', [
            'name' => 'Colgate Toothpaste',
            'code' => 'COLG-001',
            'price' => 50,
            'tax_percentage' => 12,
            'stock_quantity' => 100,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Colgate Toothpaste');
        $response->assertJsonPath('data.code', 'COLG-001');

        $this->assertDatabaseHas('products', [
            'name' => 'Colgate Toothpaste',
            'code' => 'COLG-001',
            'price' => 50,
            'tax_percentage' => 12,
            'stock_quantity' => 100,
        ]);
    }

    public function test_it_requires_all_fields(): void
    {
        $this->postJson('/products', [])
            ->assertJsonValidationErrors(['name', 'code', 'price', 'tax_percentage', 'stock_quantity']);
    }

    public function test_it_rejects_a_duplicate_product_code(): void
    {
        Product::factory()->create(['code' => 'DUPLICATE-1']);

        $this->postJson('/products', [
            'name' => 'Another Product',
            'code' => 'DUPLICATE-1',
            'price' => 10,
            'tax_percentage' => 0,
            'stock_quantity' => 5,
        ])->assertJsonValidationErrors('code');
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $this->postJson('/products', [
            'name' => 'Bad Price',
            'code' => 'BAD-1',
            'price' => -5,
            'tax_percentage' => 0,
            'stock_quantity' => 5,
        ])->assertJsonValidationErrors('price');
    }

    public function test_it_rejects_a_tax_percentage_over_100(): void
    {
        $this->postJson('/products', [
            'name' => 'Bad Tax',
            'code' => 'BAD-2',
            'price' => 10,
            'tax_percentage' => 150,
            'stock_quantity' => 5,
        ])->assertJsonValidationErrors('tax_percentage');
    }
}
