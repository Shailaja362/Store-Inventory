@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')
    <x-page-header
        title="Order {{ $order->order_number }}"
        subtitle="Placed {{ $order->created_at->format('d M Y, H:i') }}"
        back="{{ route('orders.index') }}"
        backLabel="Back to orders">
        <x-slot:actions>
            <a href="{{ route('orders.pdf', $order) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download PDF
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60 lg:col-span-2">
            <div class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-4 sm:px-6">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </span>
                <h2 class="text-base font-semibold text-slate-900">Customer</h2>
            </div>
            <div class="flex items-center gap-3 p-5 sm:p-6">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-bold text-white">
                    {{ strtoupper(substr($order->customer->name, 0, 1)) }}
                </span>
                <div>
                    <p class="font-semibold text-slate-900">{{ $order->customer->name }}</p>
                    <p class="text-sm text-slate-500">{{ $order->customer->email }}</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-900 to-indigo-950 px-5 py-4">
                <h2 class="text-base font-semibold text-white">Order Total</h2>
            </div>
            <div class="p-5">
                <p class="text-3xl font-extrabold text-indigo-600">{{ number_format($order->grand_total, 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $order->items->count() }} item(s) on this order</p>
            </div>
        </section>
    </div>

    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-4 sm:px-6">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </span>
            <h2 class="text-base font-semibold text-slate-900">Items</h2>
        </div>

        <div class="overflow-x-auto p-5 sm:p-6">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2 text-center">Qty</th>
                        <th class="px-3 py-2 text-right">Unit Price</th>
                        <th class="px-3 py-2 text-right">Tax %</th>
                        <th class="px-3 py-2 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <tr class="hover:bg-indigo-50/30">
                            <td class="px-3 py-2.5">{{ $item->product->name }} <span class="text-slate-400">({{ $item->product->code }})</span></td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex min-w-[1.75rem] items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $item->quantity }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-2.5 text-right">{{ number_format($item->tax_percentage, 2) }}%</td>
                            <td class="px-3 py-2.5 text-right font-semibold text-slate-900">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <dl class="mt-5 ml-auto max-w-xs space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-medium text-slate-800">{{ number_format($order->subtotal, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd class="font-medium text-slate-800">{{ number_format($order->tax_total, 2) }}</dd></div>
                <div class="flex justify-between border-t border-dashed border-slate-300 pt-2 font-bold text-slate-900"><dt>Grand Total</dt><dd>{{ number_format($order->grand_total, 2) }}</dd></div>
                @if (! is_null($order->amount_paid))
                    <div class="flex justify-between"><dt class="text-slate-500">Amount Paid</dt><dd class="font-medium text-slate-800">{{ number_format($order->amount_paid, 2) }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">{{ $order->amount_paid >= $order->grand_total ? 'Balance Returned' : 'Balance Due' }}</dt>
                        <dd class="font-semibold {{ $order->amount_paid >= $order->grand_total ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format(abs($order->amount_paid - $order->grand_total), 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
        <div class="flex items-center gap-2.5 border-b border-slate-100 px-5 py-4 sm:px-6">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                </svg>
            </span>
            <h2 class="text-base font-semibold text-slate-900">Inventory Activity Log</h2>
        </div>

        <div class="overflow-x-auto p-5 sm:p-6">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2 text-center">Change</th>
                        <th class="px-3 py-2 text-center">Previous Stock</th>
                        <th class="px-3 py-2 text-center">New Stock</th>
                        <th class="px-3 py-2">Recorded At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->stockMovements as $movement)
                        <tr class="hover:bg-indigo-50/30">
                            <td class="px-3 py-2.5">{{ $movement->product->name }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600">{{ $movement->quantity_change }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center text-slate-600">{{ $movement->previous_stock }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold text-slate-900">{{ $movement->new_stock }}</td>
                            <td class="px-3 py-2.5 text-slate-500">{{ $movement->created_at->format('d M Y, H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
