<?php

namespace App\Filament\Resources\LeaveBalances\Schemas;

use App\Models\LeaveBalance;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveBalanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Employee'),

                TextEntry::make('leaveType.name')
                    ->label('Leave Type'),

                TextEntry::make('year'),

                TextEntry::make('entitled_days')
                    ->label('Entitled Days'),

                TextEntry::make('carried_over_days')
                    ->label('Carried Over Days'),

                TextEntry::make('used_days')
                    ->label('Used Days')
                    ->state(fn (LeaveBalance $record): float => $record->used_days),

                TextEntry::make('remaining_days')
                    ->label('Remaining Days')
                    ->state(fn (LeaveBalance $record): float => $record->remaining_days),
            ]);
    }
}
