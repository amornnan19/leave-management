<?php

namespace App\Filament\Resources\LeaveBalances;

use App\Filament\Resources\LeaveBalances\Pages\CreateLeaveBalance;
use App\Filament\Resources\LeaveBalances\Pages\EditLeaveBalance;
use App\Filament\Resources\LeaveBalances\Pages\ListLeaveBalances;
use App\Filament\Resources\LeaveBalances\Pages\ViewLeaveBalance;
use App\Filament\Resources\LeaveBalances\Schemas\LeaveBalanceForm;
use App\Filament\Resources\LeaveBalances\Schemas\LeaveBalanceInfolist;
use App\Filament\Resources\LeaveBalances\Tables\LeaveBalancesTable;
use App\Models\LeaveBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Leave';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return LeaveBalanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveBalanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveBalancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveBalances::route('/'),
            'create' => CreateLeaveBalance::route('/create'),
            'view' => ViewLeaveBalance::route('/{record}'),
            'edit' => EditLeaveBalance::route('/{record}/edit'),
        ];
    }
}
