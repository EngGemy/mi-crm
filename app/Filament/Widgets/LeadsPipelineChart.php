<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadsPipelineChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Pipeline';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 6;

    protected function getData(): array
    {
        $statuses = ['new', 'contacted', 'qualified', 'opportunity', 'won', 'lost'];
        $counts = [];
        $colors = [];

        foreach ($statuses as $status) {
            $counts[] = Lead::where('status', $status)->count();
            $colors[] = match ($status) {
                'new' => '#9ca3af',
                'contacted' => '#3b82f6',
                'qualified' => '#f59e0b',
                'opportunity' => '#6366f1',
                'won' => '#22c55e',
                'lost' => '#ef4444',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => array_map(fn ($s) => Lead::STATUSES[$s] ?? $s, $statuses),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
