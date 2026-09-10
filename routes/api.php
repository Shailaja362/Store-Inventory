<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::get('/orders/history', [OrderController::class, 'history'])->name('api.orders.history');
Route::get('/products/low-stock', [ProductController::class, 'lowStock'])->name('api.products.low-stock');
