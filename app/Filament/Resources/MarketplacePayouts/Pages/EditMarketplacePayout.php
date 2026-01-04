<?php

namespace App\Filament\Resources\MarketplacePayouts\Pages;

use App\Filament\Resources\MarketplacePayouts\MarketplacePayoutResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketplacePayout extends EditRecord
{
    protected static string $resource = MarketplacePayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
