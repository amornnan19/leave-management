<?php

namespace App\Filament\Employee\Resources\MyLeaveBalances\Pages;

use App\Filament\Employee\Resources\MyLeaveBalances\MyLeaveBalanceResource;
use Filament\Resources\Pages\ListRecords;

class ListMyLeaveBalances extends ListRecords
{
    protected static string $resource = MyLeaveBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action — balances are managed by HR only.
        ];
    }
}
