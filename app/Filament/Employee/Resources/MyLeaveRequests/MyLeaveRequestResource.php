<?php

namespace App\Filament\Employee\Resources\MyLeaveRequests;

use App\Filament\Employee\Resources\MyLeaveRequests\Pages\CreateMyLeaveRequest;
use App\Filament\Employee\Resources\MyLeaveRequests\Pages\ListMyLeaveRequests;
use App\Filament\Employee\Resources\MyLeaveRequests\Pages\ViewMyLeaveRequest;
use App\Filament\Employee\Resources\MyLeaveRequests\Schemas\MyLeaveRequestForm;
use App\Filament\Employee\Resources\MyLeaveRequests\Schemas\MyLeaveRequestInfolist;
use App\Filament\Employee\Resources\MyLeaveRequests\Tables\MyLeaveRequestsTable;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyLeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'My Leave';

    protected static ?string $pluralModelLabel = 'Leave Requests';

    protected static ?string $modelLabel = 'Leave Request';

    public static function form(Schema $schema): Schema
    {
        return MyLeaveRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MyLeaveRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MyLeaveRequestsTable::configure($table);
    }

    /**
     * Hard scope: employees only ever see their own requests.
     *
     * @return Builder<LeaveRequest>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyLeaveRequests::route('/'),
            'create' => CreateMyLeaveRequest::route('/create'),
            'view' => ViewMyLeaveRequest::route('/{record}'),
        ];
    }
}
