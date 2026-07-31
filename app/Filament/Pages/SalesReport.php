<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Services\Sales\SalesRepReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'تقارير المبيعات';

    protected static ?string $title = 'تقارير أداء المبيعات';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.sales-report';

    public string $dateFrom = '';

    public string $dateTo = '';

    public array $reps = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'admin', 'sales_manager'])
            || $user?->can('reports.view_sales')
            ?? false;
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->loadReport();
    }

    public function applyPreset(string $preset): void
    {
        [$from, $to] = app(SalesRepReportService::class)->periodForMode($preset);
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->loadReport();
    }

    public function updatedDateFrom(): void
    {
        $this->loadReport();
    }

    public function updatedDateTo(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $from = $this->dateFrom ?: now()->startOfMonth()->format('Y-m-d');
        $to = $this->dateTo ?: now()->endOfMonth()->format('Y-m-d');

        $followUp = app(SalesRepReportService::class)->build('custom', $from, $to);
        $followById = collect($followUp['reps'])->keyBy('id');

        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales_rep', 'sales_manager']))->get();

        $this->reps = $salesUsers->map(function (User $user) use ($from, $to, $followById) {
            $leadsBase = Lead::where('assigned_to', $user->id);

            $totalLeads = $leadsBase->clone()->count();
            $newThisPeriod = $leadsBase->clone()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count();
            $won = $leadsBase->clone()->where('status', 'won')->count();
            $lost = $leadsBase->clone()->where('status', 'lost')->count();
            $active = $leadsBase->clone()->whereNotIn('status', ['won', 'lost'])->count();
            $pipelineValue = (float) $leadsBase->clone()->whereNotIn('status', ['won', 'lost'])->sum('estimated_budget');
            $wonValue = (float) $leadsBase->clone()->where('status', 'won')->sum('estimated_budget');

            $closed = $won + $lost;
            $conversionRate = $closed > 0 ? round(($won / $closed) * 100, 1) : 0;

            $activities = LeadActivity::where('user_id', $user->id)
                ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->selectRaw('type, COUNT(*) as cnt')
                ->groupBy('type')
                ->pluck('cnt', 'type')
                ->toArray();

            $totalActivities = array_sum($activities);
            $fu = $followById->get($user->id, []);

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
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_leads' => $totalLeads,
                'new_period' => $newThisPeriod,
                'active' => $active,
                'won' => $won,
                'lost' => $lost,
                'conversion_rate' => $conversionRate,
                'pipeline_value' => $pipelineValue,
                'won_value' => $wonValue,
                'activities' => $totalActivities,
                'activities_breakdown' => $activities,
                'tasks_completed' => $tasksCompleted,
                'tasks_pending' => $tasksPending,
                'calls' => (int) ($fu['calls'] ?? ($activities['call'] ?? 0)),
                'whatsapp' => (int) ($fu['whatsapp'] ?? ($activities['whatsapp'] ?? 0)),
                'duration_minutes' => (int) ($fu['duration_minutes'] ?? 0),
                'score' => $this->calcScore($won, $totalLeads, $totalActivities, $conversionRate),
            ];
        })->sortByDesc('score')->values()->toArray();
    }

    public function exportExcel(): StreamedResponse
    {
        $this->loadReport();
        $filename = 'sales-performance-'.$this->dateFrom.'_'.$this->dateTo.'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'المندوب', 'الإيميل', 'Leads الكلي', 'جديد الفترة', 'نشط', 'مغلق', 'مفقود',
                'التحويل %', 'Pipeline', 'قيمة مغلقة', 'مكالمات', 'واتساب', 'دقائق',
                'نشاطات', 'مهام مكتملة', 'مهام معلقة', 'النقاط',
            ]);

            foreach ($this->reps as $rep) {
                fputcsv($out, [
                    $rep['name'],
                    $rep['email'],
                    $rep['total_leads'],
                    $rep['new_period'],
                    $rep['active'],
                    $rep['won'],
                    $rep['lost'],
                    $rep['conversion_rate'],
                    $rep['pipeline_value'],
                    $rep['won_value'],
                    $rep['calls'],
                    $rep['whatsapp'],
                    $rep['duration_minutes'],
                    $rep['activities'],
                    $rep['tasks_completed'],
                    $rep['tasks_pending'],
                    $rep['score'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function calcScore(int $won, int $total, int $activities, float $rate): int
    {
        return ($won * 30) + (min($total, 20) * 2) + (min($activities, 50) * 1) + (int) ($rate / 2);
    }
}
