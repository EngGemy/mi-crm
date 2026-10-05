<?php

namespace App\Filament\Pages;

use App\Services\Poultry\ExhibitionDailyReport as ExhibitionDailyReportService;
use App\Services\Poultry\PoultryQuoteAccess;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ExhibitionDailyReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'تقرير المعرض';

    protected static ?string $title = 'تقرير المعرض';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.exhibition-daily-report';

    public string $date = '';

    public string $period = 'daily';

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
        $this->date = now()->toDateString();
        $this->period = $this->canUseDaily() ? 'daily' : 'weekly';
        $this->loadReport();
    }

    public function updatedDate(): void
    {
        $this->loadReport();
    }

    public function setPeriod(string $period): void
    {
        if ($period === 'daily' && ! $this->canUseDaily()) {
            return;
        }

        if ($period === 'weekly' && ! $this->canUseWeekly()) {
            return;
        }

        $this->period = $period;
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

    public function loadReport(): void
    {
        $day = $this->date !== '' ? Carbon::parse($this->date) : now();

        if ($this->period === 'weekly') {
            $from = $day->copy()->startOfWeek(Carbon::SATURDAY);
            $to = $from->copy()->addDays(6);
        } else {
            $from = $day;
            $to = $day;
        }

        $this->report = app(ExhibitionDailyReportService::class)->forRange($from, $to, auth()->user());
    }
}
