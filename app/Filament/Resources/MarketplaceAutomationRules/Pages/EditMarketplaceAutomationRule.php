<?php

namespace App\Filament\Resources\MarketplaceAutomationRules\Pages;

use App\Filament\Resources\MarketplaceAutomationRules\MarketplaceAutomationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceAutomationRule extends EditRecord
{
    protected static string $resource = MarketplaceAutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
