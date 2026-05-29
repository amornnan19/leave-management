<?php

namespace App\Filament\Resources\LeaveBalances\Pages;

use App\Filament\Resources\LeaveBalances\LeaveBalanceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaveBalance extends ViewRecord
{
    protected static string $resource = LeaveBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
