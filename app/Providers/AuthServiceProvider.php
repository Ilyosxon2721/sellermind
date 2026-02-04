<?php

namespace App\Providers;

use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\StockReservation;
use App\Policies\InventoryDocumentPolicy;
use App\Policies\StockReservationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        InventoryDocument::class => InventoryDocumentPolicy::class,
        StockReservation::class => StockReservationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
