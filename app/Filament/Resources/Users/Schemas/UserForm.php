<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.personal_data'))
                    ->schema([
                        TextInput::make('name')
                            ->label('Имя')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),

                Section::make(__('filament.sections.settings_access'))
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('Администратор')
                            ->default(false),
                        Select::make('locale')
                            ->label('Язык')
                            ->options([
                                'ru' => 'Русский',
                                'uz' => 'O\'zbek',
                                'en' => 'English',
                            ])
                            ->default('ru')
                            ->required(),
                        Select::make('company_id')
                            ->label('Основная компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email подтвержден')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }
}
