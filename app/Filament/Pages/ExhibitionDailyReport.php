<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Poultry\ExhibitionDailyReport as ExhibitionDailyReportService;
use App\Services\Poultry\PoultryQuoteAccess;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExhibitionDailyReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'تقرير المعرض';

    protected static ?string $title = 'تقرير المعرض';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.exhibition-daily-report';

    protected ?string $maxContentWidth = 'full';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $period = 'daily';

    public string $userId = '';

    /** @var array<int|string, string> */
    public array $userOptions = [];

    public array $report = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        return $user->hasRole('sales_rep') && (
            PoultryQuoteAccess::allows($user, 'report_daily')
            || PoultryQuoteAccess::allows($user, 'report_weekly')
        );
    }

    public function mount(): void
    {
        $this->loadUserOptions();
        $this->applyPreset($this->canUseDaily() ? 'daily' : 'weekly');
    }

    public function applyPreset(string $preset): void
    {
        if (in_array($preset, ['daily', 'yesterday'], true) && ! $this->canUseDaily()) {
            return;
        }

        if ($preset === 'weekly' && ! $this->canUseWeekly()) {
            return;
        }

        $from = match ($preset) {
            'yesterday' => now()->subDay(),
            'weekly' => now()->startOfWeek(Carbon::SATURDAY),
            'monthly' => now()->startOfMonth(),
            default => now(),
        };
        $to = match ($preset) {
            'yesterday' => now()->subDay(),
            'weekly' => now()->startOfWeek(Carbon::SATURDAY)->addDays(6),
            'monthly' => now()->endOfMonth(),
            default => now(),
        };

        $this->period = $preset;
        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();
        $this->loadReport();
    }

    public function refresh(): void
    {
        $this->period = 'custom';
        $this->loadReport();
    }

    public function canUseDaily(): bool
    {
        return PoultryQuoteAccess::allows(auth()->user(), 'report_daily');
    }

    public function canUseWeekly(): bool
    {
        return PoultryQuoteAccess::allows(auth()->user(), 'report_weekly');
    }

    public function loadUserOptions(): void
    {
        $user = auth()->user();

        if (PoultryQuoteAccess::seesOwnQuotesOnly($user)) {
            $this->userOptions = [$user->id => $user->name];
            $this->userId = (string) $user->id;

            return;
        }

        $this->userOptions = ['' => 'كل المناديب'] + User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['sales_rep', 'sales_manager']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function loadReport(): void
    {
        [$from, $to] = $this->range();
        $repId = $this->userId !== '' ? (int) $this->userId : null;

        $this->report = app(ExhibitionDailyReportService::class)->forRange($from, $to, auth()->user(), $repId);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->loadReport();
        $filename = 'exhibition-report-'.$this->dateFrom.'_'.$this->dateTo.'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['المندوب', 'التاريخ', 'الوقت', 'العميل', 'الهاتف', 'العرض', 'النوع', 'الحالة', 'الإجمالي']);

            foreach ($this->report['reps'] ?? [] as $rep) {
                if ($rep['clients'] === []) {
                    fputcsv($out, [$rep['name'], '', '', '', '', '', '', '', 0]);

                    continue;
                }

                foreach ($rep['clients'] as $client) {
                    fputcsv($out, [
                        $rep['name'],
                        $client['on'],
                        $client['at'],
                        $client['name'],
                        $client['phone'],
                        $client['quote_number'],
                        $client['project'],
                        $client['status'],
                        $client['total'],
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(): array
    {
        $from = $this->dateFrom !== '' ? Carbon::parse($this->dateFrom)->startOfDay() : now()->startOfDay();
        $to = $this->dateTo !== '' ? Carbon::parse($this->dateTo)->endOfDay() : $from->copy()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();

        return [$from, $to];
    }
}
