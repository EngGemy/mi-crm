<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Poultry\PoultryQuoteAccess;
use App\Services\Sales\ClientRepsReportService;
use App\Support\PermissionGate;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientRepsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'تقرير العملاء';

    protected static ?string $title = 'مين تعامل مع كل عميل';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.client-reps-report';

    protected ?string $maxContentWidth = 'full';

    public string $search = '';

    public string $userId = '';

    /** @var array<int|string, string> */
    public array $userOptions = [];

    /** @var list<array{name: string, phone: string, company: string, quotes: int, reps: list<array{id: int, name: string, roles: string}>}> */
    public array $rows = [];

    public static function canAccess(): bool
    {
        return PermissionGate::allows(
            'reports.view_sales',
            'quotations.view_any',
            'quotations.view_own',
        );
    }

    public function mount(): void
    {
        $this->loadUserOptions();
        $this->loadReport();
    }

    public function updatedSearch(): void
    {
        $this->loadReport();
    }

    public function updatedUserId(): void
    {
        $this->loadReport();
    }

    public function loadUserOptions(): void
    {
        $this->userOptions = ['' => 'كل المناديب'] + User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['sales_rep', 'sales_manager']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function loadReport(): void
    {
        $repId = $this->userId !== '' ? (int) $this->userId : null;
        $rows = app(ClientRepsReportService::class)->rows($this->search, $repId);
        $user = auth()->user();

        if ($user && PoultryQuoteAccess::seesOwnQuotesOnly($user)) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => collect($row['reps'])->contains(
                    fn (array $rep): bool => (int) $rep['id'] === (int) $user->id
                )
            ));
        }

        $this->rows = $rows;
    }

    public function exportExcel(): StreamedResponse
    {
        $filename = 'client-reps-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['العميل', 'الموبايل', 'الشركة', 'عروض السعر', 'المندوب', 'التعامل']);

            foreach ($this->rows as $row) {
                foreach ($row['reps'] as $rep) {
                    fputcsv($out, [
                        $row['name'],
                        $row['phone'],
                        $row['company'],
                        $row['quotes'],
                        $rep['name'],
                        $rep['roles'],
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
