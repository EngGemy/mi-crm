<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PipelineSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return \App\Support\PermissionGate::allows('leads.view_any', 'leads.view_own');
    }

    protected function getStats(): array
    {
        $query = \App\Support\PermissionGate::scopeLeads(Lead::query());

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
