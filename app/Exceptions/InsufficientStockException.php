<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly Product $product, public readonly int $requestedQuantity)
    {
        parent::__construct(sprintf(
            'Insufficient stock for product "%s" (code: %s). Requested %d, only %d available.',
            $product->name,
            $product->code,
            $requestedQuantity,
            $product->stock_quantity,
        ));
    }
}
