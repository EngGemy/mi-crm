<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadsBySourceChart extends ChartWidget
{
    protected static ?string $heading = 'Leads حسب المصدر';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 6;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        foreach (Lead::SOURCES as $key => $label) {
            $count = Lead::where('source', $key)->count();
            if ($count > 0) {
                $labels[] = $label;
                $data[] = $count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $data,
                    'backgroundColor' => [
                        '#3b82f6', '#22c55e', '#f97316', '#06b6d4',
                        '#eab308', '#6366f1', '#ec4899', '#8b5cf6',
                        '#14b8a6', '#64748b',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
