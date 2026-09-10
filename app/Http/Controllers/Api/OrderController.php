<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
        //
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages([
                'items' => [$exception->getMessage()],
            ]);
        }

        return response()->json([
            'data' => $this->formatOrder($order),
        ], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $customer = Customer::query()->where('email', $request->string('email'))->first();

        if (! $customer) {
            return response()->json(['data' => []]);
        }

        $orders = $customer->orders()
            ->with('items.product')
            ->latest()
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order) => $this->formatOrder($order))->all(),
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     order_number: string,
     *     customer: array{id: int, name: string, email: string},
     *     items: array<int, array{
     *         id: int,
     *         product_id: int,
     *         product_name: string,
     *         product_code: string,
     *         quantity: int,
     *         unit_price: string,
     *         tax_percentage: string,
     *         line_subtotal: string,
     *         line_tax: string,
     *         line_total: string,
     *     }>,
     *     subtotal: string,
     *     tax_total: string,
     *     grand_total: string,
     *     amount_paid: ?string,
     *     balance: ?string,
     *     created_at: string,
     * }
     */
    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ],
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_code' => $item->product->code,
                'quantity' => $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'tax_percentage' => (string) $item->tax_percentage,
                'line_subtotal' => (string) $item->line_subtotal,
                'line_tax' => (string) $item->line_tax,
                'line_total' => (string) $item->line_total,
            ])->all(),
            'subtotal' => (string) $order->subtotal,
            'tax_total' => (string) $order->tax_total,
            'grand_total' => (string) $order->grand_total,
            'amount_paid' => $order->amount_paid !== null ? (string) $order->amount_paid : null,
            'balance' => $order->amount_paid !== null
                ? number_format($order->amount_paid - $order->grand_total, 2, '.', '')
                : null,
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }
}
