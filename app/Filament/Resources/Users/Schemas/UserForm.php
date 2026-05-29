<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                    ->exists(table: Department::class, column: 'id')
                    ->nullable(),

                Select::make('manager_id')
                    ->label('Manager')
                    ->relationship(
                        name: 'manager',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, ?User $record) => $query
                            ->whereIn('role', [UserRole::Manager->value, UserRole::Hr->value])
                            ->when($record, fn ($q) => $q->whereKeyNot($record->getKey())),
                    )
                    ->searchable()
                    ->preload()
                    ->exists(table: User::class, column: 'id')
                    ->rules([
                        fn (?User $record): \Closure => static function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                            if ($value === null || $value === '') {
                                return;
                            }

                            if ($record && (int) $value === $record->getKey()) {
                                $fail('A user cannot be their own manager.');

                                return;
                            }

                            // Enforce the manager's role server-side (not only via the option
                            // list) so a tampered manager_id pointing at an Employee is rejected.
                            $manager = User::find($value);

                            if ($manager && ! in_array($manager->role, [UserRole::Manager, UserRole::Hr], true)) {
                                $fail('The selected manager must be a Manager or HR user.');
                            }
                        },
                    ])
                    ->nullable(),

                TextInput::make('employee_code')
                    ->maxLength(50)
                    ->nullable(),

                DatePicker::make('joined_at')
                    ->nullable(),
            ]);
    }
}
