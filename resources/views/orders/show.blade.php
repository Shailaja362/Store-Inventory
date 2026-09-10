@extends('layouts.app')

@section('title', 'Order #' . $order->id)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Order #{{ $order->id }}</h1>
        <a href="{{ route('orders.index') }}" class="text-sm text-slate-600 hover:underline">&larr; Back to orders</a>
    </div>

    <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-2 text-base font-semibold text-slate-900">Customer</h2>
        <p class="text-sm text-slate-700">{{ $order->customer->name }}</p>
        <p class="text-sm text-slate-500">{{ $order->customer->email }}</p>
    </section>

    <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Items</h2>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2 text-center">Qty</th>
                    <th class="px-3 py-2 text-right">Unit Price</th>
                    <th class="px-3 py-2 text-right">Tax %</th>
                    <th class="px-3 py-2 text-right">Line Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-3 py-2">{{ $item->product->name }} <span class="text-slate-400">({{ $item->product->code }})</span></td>
                        <td class="px-3 py-2 text-center">{{ $item->quantity }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($item->tax_percentage, 2) }}%</td>
                        <td class="px-3 py-2 text-right font-medium">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="mt-4 ml-auto max-w-xs space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ number_format($order->subtotal, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd>{{ number_format($order->tax_total, 2) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold"><dt>Grand Total</dt><dd>{{ number_format($order->grand_total, 2) }}</dd></div>
            @if (! is_null($order->amount_paid))
                <div class="flex justify-between"><dt class="text-slate-500">Amount Paid</dt><dd>{{ number_format($order->amount_paid, 2) }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ $order->amount_paid >= $order->grand_total ? 'Balance Returned' : 'Balance Due' }}</dt>
                    <dd>{{ number_format(abs($order->amount_paid - $order->grand_total), 2) }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Inventory Activity Log</h2>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2 text-center">Change</th>
                    <th class="px-3 py-2 text-center">Previous Stock</th>
                    <th class="px-3 py-2 text-center">New Stock</th>
                    <th class="px-3 py-2">Recorded At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->stockMovements as $movement)
                    <tr>
                        <td class="px-3 py-2">{{ $movement->product->name }}</td>
                        <td class="px-3 py-2 text-center text-red-600">{{ $movement->quantity_change }}</td>
                        <td class="px-3 py-2 text-center">{{ $movement->previous_stock }}</td>
                        <td class="px-3 py-2 text-center">{{ $movement->new_stock }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $movement->created_at->format('d M Y, H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
