<?php

namespace App\Filament\Employee\Resources\MyLeaveRequests\Pages;

use App\Filament\Employee\Resources\MyLeaveRequests\MyLeaveRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyLeaveRequests extends ListRecords
{
    protected static string $resource = MyLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
