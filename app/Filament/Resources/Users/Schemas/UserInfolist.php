<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('role')
                    ->badge(),
                TextEntry::make('department.name')
                    ->label('Department'),
                TextEntry::make('manager.name')
                    ->label('Manager'),
                TextEntry::make('employee_code')
                    ->label('Employee Code'),
                TextEntry::make('joined_at')
                    ->label('Joined At')
                    ->date(),
            ]);
    }
}
