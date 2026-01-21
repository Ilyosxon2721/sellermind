<?php

namespace App\Filament\Resources\InventoryDocuments\Schemas;

use App\Models\Warehouse\InventoryDocument;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.basic_info'))
                    ->schema([
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('doc_no')
                            ->label('Номер документа')
                            ->placeholder('Автоматически')
                            ->maxLength(50),
                        Select::make('type')
                            ->label('Тип документа')
                            ->options([
                                InventoryDocument::TYPE_IN => 'Приход',
                                InventoryDocument::TYPE_OUT => 'Расход',
                                InventoryDocument::TYPE_MOVE => 'Перемещение',
                                InventoryDocument::TYPE_WRITE_OFF => 'Списание',
                                InventoryDocument::TYPE_INVENTORY => 'Инвентаризация',
                                InventoryDocument::TYPE_REVERSAL => 'Сторнирование',
                            ])
                            ->required(),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                InventoryDocument::STATUS_DRAFT => 'Черновик',
                                InventoryDocument::STATUS_POSTED => 'Проведен',
                                InventoryDocument::STATUS_CANCELLED => 'Отменен',
                            ])
                            ->default(InventoryDocument::STATUS_DRAFT)
                            ->required(),
                    ])->columns(2),

                Section::make('Склады')
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Склад (откуда)')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('warehouse_to_id')
                            ->label('Склад (куда)')
                            ->relationship('warehouseTo', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === InventoryDocument::TYPE_MOVE),
                    ])->columns(2),

                Section::make(__('filament.sections.additional'))
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Поставщик')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === InventoryDocument::TYPE_IN),
                        TextInput::make('source_doc_no')
                            ->label('Номер внешнего документа'),
                        Textarea::make('reason')
                            ->label('Причина')
                            ->rows(2),
                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->rows(2),
                    ])->columns(2),

                Section::make(__('filament.sections.timestamps'))
                    ->schema([
                        DateTimePicker::make('posted_at')
                            ->label('Дата проведения')
                            ->disabled(),
                        TextInput::make('created_by')
                            ->label('Создал')
                            ->disabled(),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }
}
