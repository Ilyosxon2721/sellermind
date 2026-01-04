<?php

namespace App\Filament\Resources\MarketplacePayouts\Pages;

use App\Filament\Resources\MarketplacePayouts\MarketplacePayoutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketplacePayouts extends ListRecords
{
    protected static string $resource = MarketplacePayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
