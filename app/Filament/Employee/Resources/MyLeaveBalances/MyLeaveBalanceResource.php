<?php

namespace App\Filament\Employee\Resources\MyLeaveBalances;

use App\Filament\Employee\Resources\MyLeaveBalances\Pages\ListMyLeaveBalances;
use App\Models\LeaveBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyLeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'My Balances';

    protected static ?string $pluralModelLabel = 'Leave Balances';

    protected static ?string $modelLabel = 'Leave Balance';

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Authorize the portal list directly: the LeaveBalance model policy is HR-only
     * (it guards the unscoped admin resource). Here every authenticated user may view
     * their OWN balances, which getEloquentQuery() restricts to their rows.
     */
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    /**
     * Hard scope: employees only ever see their own balance rows.
     *
     * @return Builder<LeaveBalance>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('year')
                    ->sortable(),

                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable(),

                TextColumn::make('entitled_days')
                    ->label('Entitled')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),

                TextColumn::make('carried_over_days')
                    ->label('Carried Over')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),

                // NOTE: used_days and remaining_days are computed accessors that each
                // fire one DB query per row. This is acceptable because an employee
                // sees only their own handful of rows. At admin scale (all employees)
                // these would be N+1 — use eager-aggregation instead.
                TextColumn::make('used_days')
                    ->label('Used')
                    ->numeric(decimalPlaces: 1)
                    ->getStateUsing(fn (LeaveBalance $record): float => $record->used_days),

                TextColumn::make('remaining_days')
                    ->label('Remaining')
                    ->numeric(decimalPlaces: 1)
                    ->getStateUsing(fn (LeaveBalance $record): float => $record->remaining_days),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyLeaveBalances::route('/'),
        ];
    }
}
