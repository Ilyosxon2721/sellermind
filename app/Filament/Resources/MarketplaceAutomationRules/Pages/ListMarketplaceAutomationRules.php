<?php

namespace App\Filament\Resources\MarketplaceAutomationRules\Pages;

use App\Filament\Resources\MarketplaceAutomationRules\MarketplaceAutomationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceAutomationRules extends ListRecords
{
    protected static string $resource = MarketplaceAutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
