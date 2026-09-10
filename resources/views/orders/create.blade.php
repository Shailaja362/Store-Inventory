@extends('layouts.app')

@section('title', 'New Order')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/vendor/choices/choices.min.css') }}">
    <style>
        .choices {
            margin-bottom: 0;
        }
        .choices__inner {
            min-height: 2.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #cbd5e1;
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            font-size: 0.875rem;
        }
        .choices.is-focused .choices__inner,
        .choices.is-open .choices__inner {
            border-color: #64748b;
            box-shadow: 0 0 0 1px #64748b;
        }
        .choices__list--dropdown,
        .choices__list[aria-expanded] {
            border-color: #cbd5e1;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #f1f5f9;
        }
        .choices__input {
            background-color: transparent;
            font-size: 0.875rem;
        }
        .qty-input::-webkit-inner-spin-button,
        .qty-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .qty-input {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
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
        <section class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
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
                    <label for="customer_name" class="block text-sm font-medium text-slate-700">New Customer Name <span class="text-red-600">*</span></label>
                    <input type="text" id="customer_name" name="customer_name"
                           placeholder="e.g. Thomas Anderson"
                           class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 disabled:bg-slate-100 disabled:text-slate-400">
                    <p class="mt-1 text-xs text-red-600 hidden" data-error-for="customer_name"></p>
                </div>
                <div>
                    <label for="customer_email" class="block text-sm font-medium text-slate-700">New Customer Email <span class="text-red-600">*</span></label>
                    <input type="email" id="customer_email" name="customer_email"
                           placeholder="e.g. thomas@example.com"
                           class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 disabled:bg-slate-100 disabled:text-slate-400">
                    <p class="mt-1 text-xs text-amber-600 hidden" id="customer-exists-note"></p>
                    <p class="mt-1 text-xs text-red-600 hidden" data-error-for="customer_email"></p>
                </div>
            </div>
        </section>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Product card --}}
            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-slate-900">Products</h2>
                    <button type="button" id="open-product-modal"
                            class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        + New Product
                    </button>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="min-w-full border border-slate-200 text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <th class="border border-slate-200 px-3 py-2">Product</th>
                                <th class="border border-slate-200 px-3 py-2 w-32">Qty</th>
                                <th class="border border-slate-200 px-3 py-2 text-right">Price</th>
                                <th class="border border-slate-200 px-3 py-2 text-right">Line Total</th>
                                <th class="border border-slate-200 px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="product-rows">
                            <tr id="no-products-row">
                                <td colspan="5" class="border border-slate-200 px-3 py-6 text-center text-sm text-slate-400">
                                    No products added yet. Select a product below to add it.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 w-full sm:max-w-sm">
                    <label for="product-picker" class="block text-sm font-medium text-slate-700">Add Product</label>
                    <select id="product-picker" class="mt-1 block w-full">
                        <option value="">Select a product&hellip;</option>
                    </select>
                    <p class="mt-1 text-xs text-red-600 hidden" data-error-for="items"></p>
                </div>
            </section>

            {{-- Low stock alert card --}}
            <section class="self-start rounded-lg border border-amber-300 bg-amber-50 p-4 shadow-sm sm:p-5">
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-amber-800">
                    <span aria-hidden="true">&#9888;</span> Low Stock Alert
                </h2>

                @if ($lowStockProducts->isEmpty())
                    <p class="text-sm text-amber-700">All products are currently well stocked.</p>
                @else
                    <ul class="max-h-80 space-y-1.5 overflow-y-auto text-sm text-amber-900">
                        @foreach ($lowStockProducts as $product)
                            <li class="flex items-center justify-between gap-2">
                                <span>{{ $product->name }}</span>
                                <span class="shrink-0 font-semibold {{ $product->stock_quantity <= 0 ? 'text-red-600' : 'text-amber-700' }}">
                                    {{ $product->stock_quantity }} left
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Payment --}}
        <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm max-w-sm ml-auto">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Payment</h2>

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

            <div class="mt-4">
                <label for="amount_paid" class="block text-sm font-medium text-slate-700">Amount Given by Customer <span class="text-red-600">*</span></label>
                <input type="number" id="amount_paid" name="amount_paid" min="1" step="0.01" required
                       placeholder="0.00"
                       class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                <p class="mt-1 text-xs text-red-600 hidden" data-error-for="amount_paid"></p>
            </div>

            <div class="mt-3 flex justify-between text-sm">
                <span class="text-slate-500" id="balance-label">Balance to Return</span>
                <span id="summary-balance" class="font-semibold text-slate-900">0.00</span>
            </div>

            <button type="submit" id="generate-bill"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-500 disabled:opacity-50">
                Generate Bill
            </button>
        </section>
    </form>

    {{-- Bill preview --}}
    <section id="bill-preview" class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm hidden">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Bill Preview &mdash; Order #<span id="bill-order-id"></span></h2>
            <div class="flex items-center gap-3">
                <a id="bill-download-pdf" href="#" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download PDF
                </a>
                <button type="button" id="new-order-btn" class="text-sm text-slate-600 hover:underline">Start a new order</button>
            </div>
        </div>

        <p class="mb-4 text-sm text-slate-500">
            <span id="bill-customer-name" class="font-medium text-slate-700"></span>
            &middot; <span id="bill-customer-email"></span>
        </p>

        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2 text-center">Qty</th>
                    <th class="px-3 py-2 text-right">Price</th>
                    <th class="px-3 py-2 text-right">Line Total</th>
                </tr>
            </thead>
            <tbody id="bill-items"></tbody>
        </table>

        <dl class="mt-4 ml-auto max-w-xs space-y-2 text-sm">
            <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                <dt>Grand Total</dt>
                <dd id="bill-grand-total"></dd>
            </div>
            <div id="bill-amount-paid-row" class="flex justify-between">
                <dt class="text-slate-500">Amount Paid</dt>
                <dd id="bill-amount-paid"></dd>
            </div>
            <div id="bill-balance-row" class="flex justify-between">
                <dt class="text-slate-500" id="bill-balance-label">Remaining Balance</dt>
                <dd id="bill-balance"></dd>
            </div>
        </dl>
    </section>

    {{-- New product modal --}}
    <div id="new-product-modal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-slate-900/50" id="new-product-backdrop"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">New Product</h3>
                    <button type="button" id="close-product-modal" class="text-slate-400 hover:text-slate-600" aria-label="Close">&times;</button>
                </div>

                <div id="product-form-alert" class="mb-4 hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></div>

                <form id="new-product-form" novalidate>
                    <div class="space-y-4">
                        <div>
                            <label for="product_name" class="block text-sm font-medium text-slate-700">Name <span class="text-red-600">*</span></label>
                            <input type="text" id="product_name" name="name" required
                                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                            <p class="mt-1 text-xs text-red-600 hidden" data-error-for="name"></p>
                        </div>
                        <div>
                            <label for="product_code" class="block text-sm font-medium text-slate-700">Unique Code <span class="text-red-600">*</span></label>
                            <input type="text" id="product_code" name="code" required
                                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                            <p class="mt-1 text-xs text-red-600 hidden" data-error-for="code"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="product_price" class="block text-sm font-medium text-slate-700">Price Per Unit <span class="text-red-600">*</span></label>
                                <input type="number" id="product_price" name="price" min="0.01" step="0.01" required
                                       class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                                <p class="mt-1 text-xs text-red-600 hidden" data-error-for="price"></p>
                            </div>
                            <div>
                                <label for="product_tax" class="block text-sm font-medium text-slate-700">Tax Percentage <span class="text-red-600">*</span></label>
                                <input type="number" id="product_tax" name="tax_percentage" min="0" max="100" step="0.01" required
                                       class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                                <p class="mt-1 text-xs text-red-600 hidden" data-error-for="tax_percentage"></p>
                            </div>
                        </div>
                        <div>
                            <label for="product_stock" class="block text-sm font-medium text-slate-700">Stock <span class="text-red-600">*</span></label>
                            <input type="number" id="product_stock" name="stock_quantity" min="0" step="1" required
                                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                            <p class="mt-1 text-xs text-red-600 hidden" data-error-for="stock_quantity"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" id="cancel-product-modal"
                                class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" id="save-product-btn"
                                class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script id="products-data" type="application/json">@json($products)</script>
    <script id="customers-data" type="application/json">@json($customers)</script>
    <script>
        window.orderFormConfig = {
            lowStockThreshold: {{ (int) $lowStockThreshold }},
            storeUrl: @json(route('orders.store')),
            createUrl: @json(route('orders.create')),
            productStoreUrl: @json(route('products.store')),
            orderPdfUrlTemplate: @json(route('orders.pdf', ['order' => '__ORDER_ID__'])),
        };
    </script>
@endsection

@push('scripts')
    <script src="{{ asset('admin/vendor/choices/choices.min.js') }}"></script>
    <script src="{{ asset('admin/js/order-create.js') }}"></script>
@endpush
