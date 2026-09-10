<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_a_pdf_for_an_order(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $order = Order::factory()->for($customer)->create([
            'subtotal' => 100,
            'tax_total' => 10,
            'grand_total' => 110,
            'amount_paid' => 150,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50,
            'tax_percentage' => 10,
            'line_subtotal' => 100,
            'line_tax' => 10,
            'line_total' => 110,
        ]);

        $response = $this->get("/orders/{$order->id}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
