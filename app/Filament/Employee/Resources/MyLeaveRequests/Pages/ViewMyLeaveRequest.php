<?php

namespace App\Filament\Employee\Resources\MyLeaveRequests\Pages;

use App\Filament\Employee\Resources\MyLeaveRequests\MyLeaveRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMyLeaveRequest extends ViewRecord
{
    protected static string $resource = MyLeaveRequestResource::class;
}
