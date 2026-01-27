<?php

namespace App\Filament\Resources\InventoryDocuments;

use App\Filament\Resources\InventoryDocuments\Pages\CreateInventoryDocument;
use App\Filament\Resources\InventoryDocuments\Pages\EditInventoryDocument;
use App\Filament\Resources\InventoryDocuments\Pages\ListInventoryDocuments;
use App\Filament\Resources\InventoryDocuments\Schemas\InventoryDocumentForm;
use App\Filament\Resources\InventoryDocuments\Tables\InventoryDocumentsTable;
use App\Models\Warehouse\InventoryDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryDocumentResource extends Resource
{
    protected static ?string $model = InventoryDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('filament.resources.inventory_document.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.inventory_document.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav_groups.warehouse');
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryDocumentsTable::configure($table);
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
            'index' => ListInventoryDocuments::route('/'),
            'create' => CreateInventoryDocument::route('/create'),
            'edit' => EditInventoryDocument::route('/{record}/edit'),
        ];
    }
}
