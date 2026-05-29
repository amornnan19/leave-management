<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
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

                Select::make('head_user_id')
                    ->label('Head of Department')
                    ->relationship(name: 'head', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }
}
