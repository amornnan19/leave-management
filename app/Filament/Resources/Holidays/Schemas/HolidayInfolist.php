<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class HolidayInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('date')
                    ->date(),
                IconEntry::make('is_recurring')
                    ->label('Recurring Annually')
                    ->boolean(),
            ]);
    }
}
