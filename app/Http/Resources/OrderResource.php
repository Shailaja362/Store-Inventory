<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'subtotal' => (string) $this->subtotal,
            'tax_total' => (string) $this->tax_total,
            'grand_total' => (string) $this->grand_total,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
