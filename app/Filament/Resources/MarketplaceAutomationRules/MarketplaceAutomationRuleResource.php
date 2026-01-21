<?php

namespace App\Filament\Resources\MarketplaceAutomationRules;

use App\Filament\Resources\MarketplaceAutomationRules\Pages\CreateMarketplaceAutomationRule;
use App\Filament\Resources\MarketplaceAutomationRules\Pages\EditMarketplaceAutomationRule;
use App\Filament\Resources\MarketplaceAutomationRules\Pages\ListMarketplaceAutomationRules;
use App\Filament\Resources\MarketplaceAutomationRules\Schemas\MarketplaceAutomationRuleForm;
use App\Filament\Resources\MarketplaceAutomationRules\Tables\MarketplaceAutomationRulesTable;
use App\Models\MarketplaceAutomationRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketplaceAutomationRuleResource extends Resource
{
    protected static ?string $model = MarketplaceAutomationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?int $navigationSort = 12;

    public static function getModelLabel(): string
    {
        return __('filament.resources.marketplace_automation_rule.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.marketplace_automation_rule.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav_groups.marketplaces');
    }

    public static function form(Schema $schema): Schema
    {
        return MarketplaceAutomationRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceAutomationRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketplaceAutomationRules::route('/'),
            'create' => CreateMarketplaceAutomationRule::route('/create'),
            'edit' => EditMarketplaceAutomationRule::route('/{record}/edit'),
        ];
    }
}
