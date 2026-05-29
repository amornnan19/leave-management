<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class, 'email', ignoreRecord: true),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->nullable(),

                Select::make('role')
                    ->options(UserRole::class)
                    ->required(),

                Select::make('department_id')
                    ->relationship(name: 'department', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('manager_id')
                    ->label('Manager')
                    ->relationship(name: 'manager', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('employee_code')
                    ->maxLength(50)
                    ->nullable(),

                DatePicker::make('joined_at')
                    ->nullable(),
            ]);
    }
}
