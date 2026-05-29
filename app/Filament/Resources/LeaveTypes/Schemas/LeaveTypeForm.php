<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                ColorPicker::make('color')
                    ->nullable(),

                Toggle::make('is_paid')
                    ->label('Paid Leave')
                    ->default(true),

                Toggle::make('requires_attachment')
                    ->label('Requires Attachment')
                    ->default(false),

                TextInput::make('default_days_per_year')
                    ->label('Default Days Per Year')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                TextInput::make('max_consecutive_days')
                    ->label('Max Consecutive Days')
                    ->numeric()
                    ->nullable()
                    ->minValue(1),

                TextInput::make('min_notice_days')
                    ->label('Min Notice Days')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
