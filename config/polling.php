<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Polling Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable polling system globally
    |
    */

    'enabled' => env('POLLING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Polling Intervals
    |--------------------------------------------------------------------------
    |
    | Default polling intervals in seconds for different types of data
    |
    */

    'intervals' => [
        'orders' => env('POLLING_INTERVAL_ORDERS', 15),
        'sync' => env('POLLING_INTERVAL_SYNC', 10),
        'notifications' => env('POLLING_INTERVAL_NOTIFICATIONS', 20),
        'dashboard' => env('POLLING_INTERVAL_DASHBOARD', 30),
        'supplies' => env('POLLING_INTERVAL_SUPPLIES', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time to live for polling responses cache in seconds
    |
    */

    'cache_ttl' => env('POLLING_CACHE_TTL', 5),

];
