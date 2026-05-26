<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PipelineSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $user = Auth::user();
        $query = Lead::query();

        if ($user?->hasRole('sales_rep')) {
            $query->where('assigned_to', $user->id);
        }

        $stats = [];
        foreach (Lead::STATUSES as $status => $label) {
            $count = $query->clone()->where('status', $status)->count();
            $budget = (float) $query->clone()->where('status', $status)->sum('estimated_budget');

            $stats[] = Stat::make($label, $count)
                ->description($budget > 0 ? number_format($budget / 1000, 0).'K ج.م' : null)
                ->color(Lead::STATUS_COLORS[$status] ?? 'gray');
        }

        return $stats;
    }
}
