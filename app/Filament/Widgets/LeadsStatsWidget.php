<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeadsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $query = Lead::query();

        if ($user && $user->hasRole('sales_rep')) {
            $query->where('assigned_to', $user->id);
        }

        $thisMonth = $query->clone()->whereMonth('created_at', now()->month);

        return [
            Stat::make('Leads جديدة (هذا الشهر)', $thisMonth->clone()->count())
                ->description('من '.$query->clone()->count().' إجمالي')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([1, 3, 5, 7, 10, 12, $thisMonth->clone()->count()]),

            Stat::make('Hot Leads', $query->clone()->hotLeads()->count())
                ->description('Score >= 70%')
                ->color('warning'),

            Stat::make('تحتاج متابعة', $query->clone()->needsFollowup()->count())
                ->description('Follow-up اليوم أو متأخر')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('معدل التحويل',
                $this->calculateConversionRate($query)
            )
                ->description('Leads تحولت لعملاء')
                ->color('success'),
        ];
    }

    protected function calculateConversionRate($query): string
    {
        $total = $query->clone()->whereIn('status', ['won', 'lost'])->count();
        if ($total === 0) {
            return '0%';
        }

        $won = $query->clone()->where('status', 'won')->count();

        return round(($won / $total) * 100, 1).'%';
    }
}
