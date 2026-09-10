<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        return response()->json([
            'data' => $this->formatProduct($product),
        ], 201);
    }

    /**
     * @return array{id: int, name: string, code: string, price: string, tax_percentage: string, stock_quantity: int}
     */
    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'price' => (string) $product->price,
            'tax_percentage' => (string) $product->tax_percentage,
            'stock_quantity' => $product->stock_quantity,
        ];
    }
}
