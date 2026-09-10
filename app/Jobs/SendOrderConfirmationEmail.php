<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order)
    {
        //
    }

    /**
     * Simulates sending an order confirmation email. No real SMTP is
     * configured for this project, so a log entry stands in for the mailer.
     */
    public function handle(): void
    {
        $this->order->loadMissing('customer', 'items.product');

        Log::info('Order confirmation email sent', [
            'order_id' => $this->order->id,
            'customer_email' => $this->order->customer->email,
            'customer_name' => $this->order->customer->name,
            'grand_total' => (string) $this->order->grand_total,
            'items' => $this->order->items->map(fn ($item) => [
                'product' => $item->product->name,
                'quantity' => $item->quantity,
                'line_total' => (string) $item->line_total,
            ])->all(),
        ]);
    }
}
