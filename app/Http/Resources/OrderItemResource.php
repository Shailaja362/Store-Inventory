<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_code' => $this->whenLoaded('product', fn () => $this->product->code),
            'quantity' => $this->quantity,
            'unit_price' => (string) $this->unit_price,
            'tax_percentage' => (string) $this->tax_percentage,
            'line_subtotal' => (string) $this->line_subtotal,
            'line_tax' => (string) $this->line_tax,
            'line_total' => (string) $this->line_total,
        ];
    }
}
