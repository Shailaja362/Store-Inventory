<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function lowStock(Request $request): JsonResponse
    {
        $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $threshold = $request->integer('threshold', config('inventory.low_stock_threshold'));

        $products = Product::query()
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->get();

        return ProductResource::collection($products)->response();
    }
}
