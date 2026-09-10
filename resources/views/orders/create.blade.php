@extends('layouts.app')

@section('title', 'New Order')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/public/assets/styles/choices.min.css">
@endpush

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">New Order</h1>
        <a href="{{ route('orders.index') }}" class="text-sm text-slate-600 hover:underline">&larr; Back to orders</a>
    </div>

    <div id="form-alert" class="mb-6 hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"></div>

    <form id="order-form" novalidate>
        @csrf

        {{-- Customer card --}}
        <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Customer</h2>

            <div class="mb-4">
                <label for="customer_select" class="block text-sm font-medium text-slate-700">Existing Customer</label>
                <select id="customer_select" name="customer_id" class="mt-1 block w-full">
                    <option value="">&mdash; New customer &mdash;</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-red-600 hidden" data-error-for="customer_id"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-slate-700">New Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name"
                           placeholder="e.g. Thomas Anderson"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm disabled:bg-slate-100 disabled:text-slate-400">
                    <p class="mt-1 text-xs text-red-600 hidden" data-error-for="customer_name"></p>
                </div>
                <div>
                    <label for="customer_email" class="block text-sm font-medium text-slate-700">New Customer Email</label>
                    <input type="email" id="customer_email" name="customer_email"
                           placeholder="e.g. thomas@example.com"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm disabled:bg-slate-100 disabled:text-slate-400">
                    <p class="mt-1 text-xs text-red-600 hidden" data-error-for="customer_email"></p>
                </div>
            </div>
        </section>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Product card --}}
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Products</h2>
                <p class="mt-1 mb-2 text-xs text-red-600 hidden" data-error-for="items"></p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2">Product</th>
                                <th class="px-3 py-2">Stock</th>
                                <th class="px-3 py-2 w-32">Qty</th>
                                <th class="px-3 py-2 text-right">Price</th>
                                <th class="px-3 py-2 text-right">Line Total</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="product-rows"></tbody>
                    </table>
                </div>

                <button type="button" id="add-product-row"
                        class="mt-4 inline-flex items-center rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    + Add Product
                </button>
            </section>

            {{-- Low stock alert card --}}
            <section class="rounded-lg border border-amber-300 bg-amber-50 p-5 shadow-sm">
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-amber-800">
                    <span aria-hidden="true">&#9888;</span> Low Stock Alert
                </h2>

                @if ($lowStockProducts->isEmpty())
                    <p class="text-sm text-amber-700">All products are currently well stocked.</p>
                @else
                    <ul class="space-y-1.5 text-sm text-amber-900">
                        @foreach ($lowStockProducts as $product)
                            <li class="flex items-center justify-between">
                                <span>{{ $product->name }}</span>
                                <span class="font-semibold {{ $product->stock_quantity <= 0 ? 'text-red-600' : 'text-amber-700' }}">
                                    {{ $product->stock_quantity }} left
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Totals --}}
        <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm max-w-sm ml-auto">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Subtotal</dt>
                    <dd id="summary-subtotal" class="font-medium">0.00</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tax</dt>
                    <dd id="summary-tax" class="font-medium">0.00</dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base">
                    <dt class="font-semibold text-slate-900">Grand Total</dt>
                    <dd id="summary-grand-total" class="font-semibold text-slate-900">0.00</dd>
                </div>
            </dl>
        </section>

        <div class="flex justify-end">
            <button type="submit" id="submit-order"
                    class="inline-flex items-center rounded-md bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50">
                Save Order
            </button>
        </div>
    </form>

    <script id="products-data" type="application/json">@json($products)</script>
    <script>
        window.orderFormConfig = {
            lowStockThreshold: {{ (int) $lowStockThreshold }},
            storeUrl: @json(route('orders.store')),
        };
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/[email protected]/public/assets/scripts/choices.min.js"></script>
    <script src="{{ asset('admin/js/order-create.js') }}"></script>
@endpush
