<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestContracts extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث العقود';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contract::query()
                    ->latest('contract_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('رقم العقد')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->wrap()
                    ->limit(25),

                Tables\Columns\TextColumn::make('contract_date')
                    ->label('التاريخ')
                    ->date('Y-m-d'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('القيمة')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('التسليم المتوقع')
                    ->date('Y-m-d')
                    ->color(fn (Contract $r) => $r->is_delayed ? 'danger' : null),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Contract::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'on_hold' => 'warning',
                        default => 'info',
                    }),
            ]);
    }
}
