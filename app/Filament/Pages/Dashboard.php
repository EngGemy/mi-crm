<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DueRemindersWidget;
use App\Filament\Widgets\ExhibitionStatsWidget;
use App\Filament\Widgets\LatestContracts;
use App\Filament\Widgets\LeadsBySourceChart;
use App\Filament\Widgets\LeadsPipelineChart;
use App\Filament\Widgets\LeadsStatsWidget;
use App\Filament\Widgets\OverduePayments;
use App\Filament\Widgets\PipelineSummaryWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'لوحة التحكم';
    protected static ?string $title           = 'لوحة التحكم';
    protected static ?int    $navigationSort  = -1;

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            LeadsStatsWidget::class,
            LeadsPipelineChart::class,
            LeadsBySourceChart::class,
            PipelineSummaryWidget::class,
            DueRemindersWidget::class,
            LatestContracts::class,
            OverduePayments::class,
            ExhibitionStatsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 12;
    }
}
