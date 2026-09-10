<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    private const ORDER_NUMBER_ASSIGNMENT_ATTEMPTS = 10;

    protected $fillable = [
        'customer_id',
        'subtotal',
        'tax_total',
        'grand_total',
        'amount_paid',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            $order->assignOrderNumber();
        });
    }

    /**
     * Assigns a random 5-digit order number, retrying on the rare chance
     * two orders roll the same number at the same time. The `order_number`
     * unique index is what actually makes this race-safe, not the retry
     * loop — the loop just gives a collision a couple of extra chances
     * instead of surfacing an error to the customer.
     */
    private function assignOrderNumber(): void
    {
        for ($attempt = 1; $attempt <= self::ORDER_NUMBER_ASSIGNMENT_ATTEMPTS; $attempt++) {
            try {
                $this->forceFill(['order_number' => 'ord'.random_int(10000, 99999)])->saveQuietly();

                return;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === self::ORDER_NUMBER_ASSIGNMENT_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
