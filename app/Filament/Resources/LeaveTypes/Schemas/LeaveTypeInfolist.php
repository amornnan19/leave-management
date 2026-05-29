<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('code'),
                ColorEntry::make('color'),
                IconEntry::make('is_paid')
                    ->label('Paid Leave')
                    ->boolean(),
                IconEntry::make('requires_attachment')
                    ->label('Requires Attachment')
                    ->boolean(),
                TextEntry::make('default_days_per_year')
                    ->label('Default Days Per Year'),
                TextEntry::make('max_consecutive_days')
                    ->label('Max Consecutive Days'),
                IconEntry::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ]);
    }
}
