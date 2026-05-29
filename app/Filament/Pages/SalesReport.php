<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Filament\Pages\Page;

class SalesReport extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'المبيعات';
    protected static ?string $navigationLabel = 'تقارير المبيعات';
    protected static ?string $title           = 'تقارير أداء المبيعات';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.sales-report';

    public string $dateFrom = '';
    public string $dateTo   = '';
    public array  $reps     = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ?? false;
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo   = now()->endOfMonth()->format('Y-m-d');
        $this->loadReport();
    }

    public function updatedDateFrom(): void { $this->loadReport(); }
    public function updatedDateTo(): void   { $this->loadReport(); }

    public function loadReport(): void
    {
        $from = $this->dateFrom ?: now()->startOfMonth()->format('Y-m-d');
        $to   = $this->dateTo   ?: now()->endOfMonth()->format('Y-m-d');

        $salesUsers = User::whereHas('roles', fn ($q) =>
            $q->whereIn('name', ['sales_rep', 'sales_manager'])
        )->get();

        $this->reps = $salesUsers->map(function (User $user) use ($from, $to) {
            $leadsBase = Lead::where('assigned_to', $user->id);

            $totalLeads    = $leadsBase->clone()->count();
            $newThisPeriod = $leadsBase->clone()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
            $won           = $leadsBase->clone()->where('status', 'won')->count();
            $lost          = $leadsBase->clone()->where('status', 'lost')->count();
            $active        = $leadsBase->clone()->whereNotIn('status', ['won', 'lost'])->count();
            $pipelineValue = (float) $leadsBase->clone()->whereNotIn('status', ['won', 'lost'])->sum('estimated_budget');
            $wonValue      = (float) $leadsBase->clone()->where('status', 'won')->sum('estimated_budget');

            $closed = $won + $lost;
            $conversionRate = $closed > 0 ? round(($won / $closed) * 100, 1) : 0;

            $activities = LeadActivity::where('user_id', $user->id)
                ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->selectRaw('type, COUNT(*) as cnt')
                ->groupBy('type')
                ->pluck('cnt', 'type')
                ->toArray();

            $totalActivities = array_sum($activities);

            $tasksCompleted = LeadActivity::where('user_id', $user->id)
                ->where('is_completed', true)
                ->whereBetween('completed_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->count();

            $tasksPending = LeadActivity::where('user_id', $user->id)
                ->where('is_completed', false)
                ->whereNotNull('scheduled_at')
                ->whereDate('scheduled_at', '<=', now())
                ->count();

            return [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'total_leads'     => $totalLeads,
                'new_period'      => $newThisPeriod,
                'active'          => $active,
                'won'             => $won,
                'lost'            => $lost,
                'conversion_rate' => $conversionRate,
                'pipeline_value'  => $pipelineValue,
                'won_value'       => $wonValue,
                'activities'      => $totalActivities,
                'activities_breakdown' => $activities,
                'tasks_completed' => $tasksCompleted,
                'tasks_pending'   => $tasksPending,
                'score'           => $this->calcScore($won, $totalLeads, $totalActivities, $conversionRate),
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    protected function calcScore(int $won, int $total, int $activities, float $rate): int
    {
        return ($won * 30) + (min($total, 20) * 2) + (min($activities, 50) * 1) + (int)($rate / 2);
    }
}
