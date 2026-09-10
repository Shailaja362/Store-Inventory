@extends('layouts.app')

@section('title', 'New Order')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/vendor/choices/choices.min.css') }}">
    <style>
        .choices {
            margin-bottom: 0;
        }
        .choices__inner {
            min-height: 2.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            font-size: 0.875rem;
        }
        .choices.is-focused .choices__inner,
        .choices.is-open .choices__inner {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.15);
        }
        .choices__list--dropdown,
        .choices__list[aria-expanded] {
            border-color: #cbd5e1;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            overflow: hidden;
        }
        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #eef2ff;
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
    <x-page-header title="New Order" subtitle="Build a bill, deduct stock, and generate a customer invoice." back="{{ route('orders.index') }}" backLabel="Back to orders" />

    <div id="form-alert" class="mb-6 hidden items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800 shadow-sm"></div>

    <form id="order-form" novalidate>
        @csrf

        {{-- Customer card --}}
        <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-4 sm:px-6">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900">Customer</h2>
            </div>

            <div class="p-5 sm:p-6">
                <div class="mb-4">
                    <label for="customer_select" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Existing Customer</label>
                    <select id="customer_select" name="customer_id" class="mt-1 block w-full">
                        <option value="">&mdash; New customer &mdash;</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="customer_id"></p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="customer_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">New Customer Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="customer_name" name="customer_name"
                               placeholder="e.g. Thomas Anderson"
                               class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15 disabled:bg-slate-100 disabled:text-slate-400">
                        <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="customer_name"></p>
                    </div>
                    <div>
                        <label for="customer_email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">New Customer Email <span class="text-rose-500">*</span></label>
                        <input type="email" id="customer_email" name="customer_email"
                               placeholder="e.g. thomas@example.com"
                               class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15 disabled:bg-slate-100 disabled:text-slate-400">
                        <p class="mt-1 text-xs text-amber-600 hidden" id="customer-exists-note"></p>
                        <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="customer_email"></p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Product card --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </span>
                        <h2 class="text-base font-semibold text-slate-900">Products</h2>
                    </div>
                    <button type="button" id="open-product-modal"
                            class="inline-flex items-center gap-1.5 rounded-xl border-2 border-indigo-600 px-3.5 py-2 text-sm font-semibold text-indigo-600 transition-colors hover:bg-indigo-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Product
                    </button>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="w-full overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-3 py-2.5">Product</th>
                                    <th class="px-3 py-2.5 w-36">Qty</th>
                                    <th class="px-3 py-2.5 text-right">Price</th>
                                    <th class="px-3 py-2.5 text-right">Line Total</th>
                                    <th class="px-3 py-2.5 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="product-rows" class="divide-y divide-slate-100">
                                <tr id="no-products-row">
                                    <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-400">
                                        No products added yet. Select a product below to add it.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5 w-full sm:max-w-sm">
                        <label for="product-picker" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Add Product</label>
                        <select id="product-picker" class="mt-1 block w-full">
                            <option value="">Select a product&hellip;</option>
                        </select>
                        <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="items"></p>
                    </div>
                </div>
            </section>

            {{-- Low stock alert card --}}
            <section class="self-start overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-b from-amber-50 to-white shadow-sm shadow-amber-100/60">
                <div class="flex items-center gap-2.5 border-b border-amber-200/70 px-5 py-4 sm:px-5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </span>
                    <h2 class="text-base font-semibold text-amber-900">Low Stock Alert</h2>
                </div>

                <div class="p-5">
                    @if ($lowStockProducts->isEmpty())
                        <p class="text-sm text-amber-700">All products are currently well stocked.</p>
                    @else
                        <ul class="max-h-80 space-y-1 overflow-y-auto text-sm">
                            @foreach ($lowStockProducts as $product)
                                <li class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-amber-100/50">
                                    <span class="flex items-center gap-2 text-amber-900">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $product->stock_quantity <= 0 ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                                        {{ $product->name }}
                                    </span>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold {{ $product->stock_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $product->stock_quantity }} left
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>

        {{-- Payment --}}
        <section class="mb-6 ml-auto max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-900 to-indigo-950 px-5 py-4">
                <h2 class="text-base font-semibold text-white">Payment Summary</h2>
            </div>

            <div class="p-5">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd id="summary-subtotal" class="font-medium text-slate-800">0.00</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Tax</dt>
                        <dd id="summary-tax" class="font-medium text-slate-800">0.00</dd>
                    </div>
                    <div class="flex justify-between border-t border-dashed border-slate-200 pt-2.5 text-base">
                        <dt class="font-bold text-slate-900">Grand Total</dt>
                        <dd id="summary-grand-total" class="font-bold text-indigo-600">0.00</dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <label for="amount_paid" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Amount Given by Customer <span class="text-rose-500">*</span></label>
                    <input type="number" id="amount_paid" name="amount_paid" min="1" step="0.01" required
                           placeholder="0.00"
                           class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15">
                    <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="amount_paid"></p>
                </div>

                <div class="mt-3 flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                    <span class="text-slate-500" id="balance-label">Balance to Return</span>
                    <span id="summary-balance" class="font-bold text-slate-900">0.00</span>
                </div>

                <button type="submit" id="generate-bill"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-600/25 transition-transform hover:-translate-y-0.5 hover:shadow-xl disabled:translate-y-0 disabled:opacity-50 disabled:shadow-none">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Generate Bill
                </button>
            </div>
        </section>
    </form>

    {{-- Bill preview --}}
    <section id="bill-preview" class="mb-6 hidden overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm shadow-emerald-100">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-100 bg-emerald-50 px-5 py-4 sm:px-6">
            <h2 class="flex items-center gap-2 text-base font-semibold text-emerald-900">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 text-emerald-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Bill Preview &mdash; Order <span id="bill-order-id"></span>
            </h2>
            <div class="flex items-center gap-3">
                <a id="bill-download-pdf" href="#"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download PDF
                </a>
                <button type="button" id="new-order-btn" class="text-sm font-medium text-indigo-600 hover:underline">Start a new order</button>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <p class="mb-4 text-sm text-slate-500">
                <span id="bill-customer-name" class="font-medium text-slate-700"></span>
                &middot; <span id="bill-customer-email"></span>
            </p>

            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2 text-center">Qty</th>
                        <th class="px-3 py-2 text-right">Price</th>
                        <th class="px-3 py-2 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody id="bill-items" class="divide-y divide-slate-100"></tbody>
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
        </div>
    </section>

    {{-- New product modal --}}
    <div id="new-product-modal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" id="new-product-backdrop"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </span>
                        New Product
                    </h3>
                    <button type="button" id="close-product-modal" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
                </div>

                <div id="product-form-alert" class="mb-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-800"></div>

                <form id="new-product-form" novalidate>
                    <div class="space-y-4">
                        <div>
                            <label for="product_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="product_name" name="name" required
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15">
                            <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="name"></p>
                        </div>
                        <div>
                            <label for="product_code" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Unique Code <span class="text-rose-500">*</span></label>
                            <input type="text" id="product_code" name="code" required
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15">
                            <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="code"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="product_price" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Price Per Unit <span class="text-rose-500">*</span></label>
                                <input type="number" id="product_price" name="price" min="0.01" step="0.01" required
                                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15">
                                <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="price"></p>
                            </div>
                            <div>
                                <label for="product_tax" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tax Percentage <span class="text-rose-500">*</span></label>
                                <input type="number" id="product_tax" name="tax_percentage" min="0" max="100" step="0.01" required
                                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15">
                                <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="tax_percentage"></p>
                            </div>
                        </div>
                        <div>
                            <label for="product_stock" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Stock <span class="text-rose-500">*</span></label>
                            <input type="number" id="product_stock" name="stock_quantity" min="0" step="1" required
                                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-3 focus:ring-indigo-500/15">
                            <p class="mt-1 text-xs text-rose-600 hidden" data-error-for="stock_quantity"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" id="cancel-product-modal"
                                class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" id="save-product-btn"
                                class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
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
