<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalContracts = Contract::count();
        $totalValue = Contract::sum('total_value');
        $totalCollected = Payment::sum('paid_amount');
        $totalDue = $totalValue - $totalCollected;

        $activeContracts = Contract::whereIn('status', [
            'signed', 'manufacturing', 'shipping', 'installing', 'testing',
        ])->count();

        $overduePayments = Payment::whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('due_date', '<', now())
            ->count();

        $deliveriesSoon = Contract::whereIn('status', ['manufacturing', 'shipping'])
            ->whereBetween('expected_delivery_date', [now(), now()->addDays(30)])
            ->count();

        $collectionRate = $totalValue > 0 ? ($totalCollected / $totalValue) * 100 : 0;

        return [
            Stat::make('إجمالي قيمة العقود', number_format($totalValue, 0).' ج.م')
                ->description("{$totalContracts} عقد")
                ->descriptionIcon('heroicon-m-document')
                ->color('primary'),

            Stat::make('إجمالي المحصّل', number_format($totalCollected, 0).' ج.م')
                ->description(number_format($collectionRate, 1).'% من قيمة العقود')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('المتبقي للتحصيل', number_format($totalDue, 0).' ج.م')
                ->description($activeContracts.' عقد جارٍ')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('دفعات متأخرة', $overduePayments)
                ->description($overduePayments > 0 ? '⚠️ يحتاج متابعة فورية' : '✓ كل الدفعات في موعدها')
                ->descriptionIcon($overduePayments > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overduePayments > 0 ? 'danger' : 'success'),

            Stat::make('تسليمات قريبة', $deliveriesSoon)
                ->description('خلال 30 يوم')
                ->descriptionIcon('heroicon-m-truck')
                ->color($deliveriesSoon > 0 ? 'info' : 'gray'),

            Stat::make('عقود جارية', $activeContracts)
                ->description('قيد التصنيع/الشحن/التركيب')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),
        ];
    }
}
