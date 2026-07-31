<?php

namespace App\Filament\Pages;

use App\Models\LeadActivity;
use App\Models\User;
use App\Services\Sales\SalesRepReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffFollowUp extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'متابعة الموظفين';

    protected static ?string $title = 'متابعة الموظفين — التقرير اليومي والشهري';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.staff-follow-up';

    public string $mode = 'daily';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?string $userId = null;

    public array $summary = [];

    public array $reps = [];

    public array $activities = [];

    public array $userOptions = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'sales_rep'])
            || $user->can('reports.view_sales');
    }

    public function mount(): void
    {
        $this->applyMode('daily');
        $this->loadUserOptions();
        $this->loadReport();
    }

    public function applyMode(string $mode): void
    {
        $this->mode = $mode;
        [$from, $to] = app(SalesRepReportService::class)->periodForMode($mode);
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->loadReport();
    }

    public function updatedDateFrom(): void
    {
        $this->mode = 'custom';
        $this->loadReport();
    }

    public function updatedDateTo(): void
    {
        $this->mode = 'custom';
        $this->loadReport();
    }

    public function updatedUserId(): void
    {
        $this->loadReport();
    }

    public function loadUserOptions(): void
    {
        $auth = auth()->user();
        $service = app(SalesRepReportService::class);

        if ($auth->hasRole('sales_rep') && ! $auth->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            $this->userOptions = [$auth->id => $auth->name];
            $this->userId = (string) $auth->id;

            return;
        }

        $this->userOptions = $service->salesUsers()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->name])
            ->all();
        $this->userOptions = ['' => 'كل المناديب'] + $this->userOptions;
    }

    public function loadReport(): void
    {
        $auth = auth()->user();
        $onlyUserId = $this->userId !== null && $this->userId !== ''
            ? (int) $this->userId
            : null;

        if ($auth->hasRole('sales_rep') && ! $auth->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            $onlyUserId = (int) $auth->id;
        }

        $report = app(SalesRepReportService::class)->build(
            $this->mode,
            $this->dateFrom ?: now()->toDateString(),
            $this->dateTo ?: now()->toDateString(),
            $onlyUserId
        );

        $this->summary = $report['summary'];
        $this->reps = $report['reps'];
        $this->activities = $report['activities'];
    }

    public function exportExcel(): StreamedResponse
    {
        $this->loadReport();

        $filename = 'staff-followup-'.$this->dateFrom.'_'.$this->dateTo.'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'المندوب',
                'النوع',
                'الموضوع',
                'الوصف',
                'النتيجة',
                'المدة (دقيقة)',
                'العميل',
                'الهاتف',
                'رقم الـ Lead',
                'مكتمل؟',
                'تاريخ التسجيل',
                'مجدول',
                'اكتمل في',
            ]);

            foreach ($this->activities as $row) {
                fputcsv($out, [
                    $row['user_name'],
                    $row['type_label'],
                    $row['subject'],
                    $row['description'],
                    $row['outcome_label'],
                    $row['duration_minutes'],
                    $row['lead_name'],
                    $row['lead_phone'],
                    $row['lead_number'],
                    $row['is_completed'] ? 'نعم' : 'لا',
                    $row['created_at'],
                    $row['scheduled_at'],
                    $row['completed_at'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['—— ملخص المناديب ——']);
            fputcsv($out, [
                'المندوب', 'مكالمات', 'واتساب', 'زيارات', 'اجتماعات', 'إجمالي النشاط',
                'دقائق', 'مكتمل', 'معلق', 'عملاء جدد', 'صفقات مغلقة',
            ]);

            foreach ($this->reps as $rep) {
                fputcsv($out, [
                    $rep['name'],
                    $rep['calls'],
                    $rep['whatsapp'],
                    $rep['visits'],
                    $rep['meetings'],
                    $rep['total_activities'],
                    $rep['duration_minutes'],
                    $rep['completed'],
                    $rep['pending'],
                    $rep['new_leads'],
                    $rep['won'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportSummaryExcel(): StreamedResponse
    {
        $this->loadReport();
        $filename = 'staff-summary-'.$this->dateFrom.'_'.$this->dateTo.'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'المندوب', 'الإيميل', 'مكالمات', 'واتساب', 'زيارات', 'اجتماعات',
                'أخرى', 'إجمالي النشاط', 'دقائق الاتصال', 'مكتمل', 'معلق',
                'عملاء جدد', 'صفقات مغلقة',
            ]);

            foreach ($this->reps as $rep) {
                fputcsv($out, [
                    $rep['name'],
                    $rep['email'],
                    $rep['calls'],
                    $rep['whatsapp'],
                    $rep['visits'],
                    $rep['meetings'],
                    $rep['other'],
                    $rep['total_activities'],
                    $rep['duration_minutes'],
                    $rep['completed'],
                    $rep['pending'],
                    $rep['new_leads'],
                    $rep['won'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function typeLabel(string $type): string
    {
        return LeadActivity::TYPES[$type] ?? $type;
    }
}
