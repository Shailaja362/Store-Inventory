<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
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

        return OrderResource::make($order)
            ->response()
            ->setStatusCode(201);
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

        return OrderResource::collection($orders)->response();
    }
}
