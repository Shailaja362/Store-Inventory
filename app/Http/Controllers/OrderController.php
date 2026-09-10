<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
        //
    }

    public function index(): View
    {
        $this->data['orders'] = Order::query()
            ->with('customer')
            ->withCount('items')
            ->latest()
            ->paginate(15);

        $this->data['stats'] = [
            'total_orders' => Order::query()->count(),
            'total_revenue' => Order::query()->sum('grand_total'),
            'low_stock_count' => Product::query()
                ->where('stock_quantity', '<=', config('inventory.low_stock_threshold'))
                ->count(),
        ];

        return view('orders.index', $this->data);
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();
        $lowStockThreshold = config('inventory.low_stock_threshold');
        $lowStockProducts = $products->where('stock_quantity', '<=', $lowStockThreshold)->sortBy('stock_quantity');

        $this->data['products'] = $products;
        $this->data['customers'] = $customers;
        $this->data['lowStockThreshold'] = $lowStockThreshold;
        $this->data['lowStockProducts'] = $lowStockProducts;

        return view('orders.create', $this->data);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());
        } catch (InsufficientStockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['items' => [$exception->getMessage()]],
            ], 422);
        }

        session()->flash('success', "Order {$order->order_number} created successfully.");

        return response()->json([
            'message' => 'Order created successfully.',
            'redirect' => route('orders.show', $order),
            'order' => $this->formatOrder($order),
        ], 201);
    }

    public function show(Order $order): View
    {
        $this->data['order'] = $order->load('customer', 'items.product', 'stockMovements.product');

        return view('orders.show', $this->data);
    }

    public function downloadPdf(Order $order): Response
    {
        $order->load('customer', 'items.product');

        $pdf = Pdf::loadView('orders.pdf', compact('order'));

        return $pdf->download("{$order->order_number}.pdf");
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
