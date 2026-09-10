<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order {{ $order->order_number }}</title>
    <style>
        body {
            font-family: "Helvetica", "Arial", sans-serif;
            color: #1e293b;
            font-size: 12px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }
        .invoice-label {
            font-size: 16px;
            font-weight: bold;
            color: #475569;
            text-align: right;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            vertical-align: top;
            padding-bottom: 4px;
        }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 4px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background-color: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }
        table.items td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.items th.text-right,
        table.items td.text-right {
            text-align: right;
        }
        table.items th.text-center,
        table.items td.text-center {
            text-align: center;
        }
        table.totals {
            width: 40%;
            margin-left: 60%;
        }
        table.totals td {
            padding: 4px 8px;
        }
        table.totals td.label {
            color: #64748b;
        }
        table.totals td.value {
            text-align: right;
        }
        table.totals tr.grand-total td {
            border-top: 2px solid #0f172a;
            font-weight: bold;
            font-size: 13px;
            padding-top: 8px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="store-name">Store Inventory</td>
            <td class="invoice-label">INVOICE</td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <div class="section-title">Billed To</div>
                {{ $order->customer->name }}<br>
                {{ $order->customer->email }}
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="section-title">Order Details</div>
                Order {{ $order->order_number }}<br>
                {{ $order->created_at->format('d M Y, H:i') }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Tax %</th>
                <th class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }} ({{ $item->product->code }})</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->tax_percentage, 2) }}%</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Tax</td>
            <td class="value">{{ number_format($order->tax_total, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total</td>
            <td class="value">{{ number_format($order->grand_total, 2) }}</td>
        </tr>
        @if (! is_null($order->amount_paid))
            <tr>
                <td class="label">Amount Paid</td>
                <td class="value">{{ number_format($order->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="label">{{ $order->amount_paid >= $order->grand_total ? 'Balance Returned' : 'Balance Due' }}</td>
                <td class="value">{{ number_format(abs($order->amount_paid - $order->grand_total), 2) }}</td>
            </tr>
        @endif
    </table>

    <div class="footer">
        Thank you for your business &mdash; Store Inventory
    </div>
</body>
</html>
