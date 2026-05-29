<?php

namespace App\Filament\Widgets;

use App\Models\Exhibition;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExhibitionStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $total    = Exhibition::count();
        $active   = Exhibition::where('status', 'active')->count();
        $planned  = Exhibition::where('status', 'planned')->count();
        $leads    = Lead::whereNotNull('exhibition_id')->count();
        $cost     = Exhibition::sum('cost');

        return [
            Stat::make('إجمالي المعارض', $total)
                ->description("{$planned} مخطط · {$active} نشط")
                ->icon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('معارض نشطة الآن', $active)
                ->description('جارية في الوقت الحالي')
                ->icon('heroicon-o-bolt')
                ->color('success'),

            Stat::make('Leads من المعارض', $leads)
                ->description('إجمالي العملاء المحتملين')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('إجمالي التكاليف', number_format($cost) . ' ج.م')
                ->description('تكاليف جميع المعارض')
                ->icon('heroicon-o-banknotes')
                ->color('warning'),
        ];
    }
}
