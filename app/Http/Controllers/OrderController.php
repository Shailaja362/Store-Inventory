<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
        //
    }

    public function index(): View
    {
        $orders = Order::query()
            ->with('customer')
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get();
        $customers = Customer::query()->orderBy('name')->get();
        $lowStockThreshold = config('inventory.low_stock_threshold');
        $lowStockProducts = $products->where('stock_quantity', '<=', $lowStockThreshold)->sortBy('stock_quantity');

        return view('orders.create', compact('products', 'customers', 'lowStockThreshold', 'lowStockProducts'));
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

        session()->flash('success', "Order #{$order->id} created successfully.");

        return response()->json([
            'message' => 'Order created successfully.',
            'redirect' => route('orders.show', $order),
        ], 201);
    }

    public function show(Order $order): View
    {
        $order->load('customer', 'items.product', 'stockMovements.product');

        return view('orders.show', compact('order'));
    }
}
