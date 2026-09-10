<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order for the given customer and product lines.
     *
     * Stock is checked and deducted inside a single database transaction
     * using row-level locking (`lockForUpdate`), so that two concurrent
     * requests competing for the same product's last units cannot both
     * succeed: whichever transaction acquires the row lock first sees the
     * up-to-date stock, and the loser either waits and then fails validation
     * against the now-reduced stock, or is retried by Laravel's deadlock
     * handling. Products are locked in a stable order (sorted by id) across
     * every request to avoid lock-ordering deadlocks between orders that
     * share multiple products.
     *
     * @param  array{customer_id: ?int, customer_email: ?string, customer_name: ?string, items: array<int, array{product_id: int, quantity: int}>}  $data
     *
     * @throws InsufficientStockException
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->resolveCustomer(
                $data['customer_id'] ?? null,
                $data['customer_email'] ?? null,
                $data['customer_name'] ?? null,
            );

            $quantitiesByProductId = $this->mergeQuantitiesByProduct($data['items']);

            $products = Product::query()
                ->whereIn('id', array_keys($quantitiesByProductId))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $taxTotal = 0;
            $lineData = [];

            foreach ($quantitiesByProductId as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product->stock_quantity < $quantity) {
                    throw new InsufficientStockException($product, $quantity);
                }

                $lineSubtotal = round((float) $product->price * $quantity, 2);
                $lineTax = round($lineSubtotal * ((float) $product->tax_percentage / 100), 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $lineData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'tax_percentage' => $product->tax_percentage,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
            ]);

            foreach ($lineData as $line) {
                $product = $line['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_percentage' => $line['tax_percentage'],
                    'line_subtotal' => $line['line_subtotal'],
                    'line_tax' => $line['line_tax'],
                    'line_total' => $line['line_total'],
                ]);

                $previousStock = $product->stock_quantity;
                $newStock = $previousStock - $line['quantity'];

                $product->update(['stock_quantity' => $newStock]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'type' => StockMovement::TYPE_SALE,
                    'quantity_change' => -$line['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                ]);
            }

            SendOrderConfirmationEmail::dispatch($order)->afterCommit();

            return $order->load('customer', 'items.product');
        });
    }

    private function resolveCustomer(?int $customerId, ?string $email, ?string $name): Customer
    {
        if ($customerId) {
            return Customer::query()->findOrFail($customerId);
        }

        $customer = Customer::query()->where('email', $email)->first();

        if ($customer) {
            return $customer;
        }

        return Customer::query()->create([
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array<int, int> quantity keyed by product_id
     */
    private function mergeQuantitiesByProduct(array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        return $quantities;
    }
}
