<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverduePayments extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '⚠️ دفعات تحتاج متابعة';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->where('due_date', '<=', now()->addDays(30))
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->badge(),

                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد'),

                Tables\Columns\TextColumn::make('contract.customer.name')
                    ->label('العميل')
                    ->wrap()
                    ->limit(25),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('expected_amount')
                    ->label('المبلغ')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->color(fn (Payment $r) => $r->is_overdue ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('days_until_due')
                    ->label('الأيام')
                    ->state(function (Payment $r) {
                        $days = $r->days_until_due;
                        if ($days === null) {
                            return '-';
                        }

                        return $days < 0
                            ? 'متأخرة '.abs($days).' يوم'
                            : $days.' يوم';
                    })
                    ->badge()
                    ->color(fn (Payment $r) => $r->days_until_due < 0 ? 'danger' : ($r->days_until_due <= 7 ? 'warning' : 'info')
                    ),
            ]);
    }
}
