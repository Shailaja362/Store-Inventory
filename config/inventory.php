<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Products with a stock_quantity at or below this value are considered
    | low stock. Can be overridden per-request via the `threshold` query
    | parameter on the low-stock endpoint.
    |
    */

    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),

];
