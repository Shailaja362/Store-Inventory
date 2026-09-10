<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_customers_orders_by_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'history@example.com']);
        $otherCustomer = Customer::factory()->create();

        $order = Order::factory()->for($customer)->create(['grand_total' => 150]);
        Order::factory()->for($otherCustomer)->create();

        $response = $this->getJson('/api/orders/history?email=history@example.com');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $order->id);
    }

    public function test_it_returns_an_empty_list_for_an_unknown_email(): void
    {
        $response = $this->getJson('/api/orders/history?email=unknown@example.com');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_it_validates_the_email_parameter(): void
    {
        $this->getJson('/api/orders/history?email=not-an-email')
            ->assertJsonValidationErrors('email');
    }
}
