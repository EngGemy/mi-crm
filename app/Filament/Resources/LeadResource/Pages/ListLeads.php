<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Exports\LeadExporter;
use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    // Maps Arabic template headers AND English keys → Lead model field names
    protected static array $headerMap = [
        // Arabic (from template)
        'الاسم' => 'name',
        'الاسم*' => 'name',
        'رقم الهاتف' => 'phone',
        'رقم الهاتف*' => 'phone',
        'رقم الواتساب' => 'whatsapp',
        'البريد الإلكتروني' => 'email',
        'الشركة / المزرعة' => 'company',
        'الشركة' => 'company',
        'المنصب' => 'position',
        'الدولة' => 'country',
        'المدينة' => 'city',
        'العنوان' => 'address',
        'نوع المشروع' => 'project_type',
        'حجم المشروع' => 'project_size',
        'الميزانية المتوقعة' => 'estimated_budget',
        'تاريخ الإغلاق المتوقع (YYYY-MM-DD)' => 'expected_close_date',
        'تاريخ الإغلاق المتوقع' => 'expected_close_date',
        'المصدر (facebook/whatsapp/instagram/website/referral/walk_in/phone_call/exhibition/cold_call/other)' => 'source',
        'المصدر' => 'source',
        'تفاصيل المصدر' => 'source_details',
        'الحالة (new/contacted/qualified/opportunity/won/lost)' => 'status',
        'الحالة' => 'status',
        'الأولوية (low/medium/high/urgent)' => 'priority',
        'الأولوية' => 'priority',
        'ملاحظات' => 'notes',

        // English keys (same as model field names)
        'name' => 'name',
        'phone' => 'phone',
        'whatsapp' => 'whatsapp',
        'email' => 'email',
        'company' => 'company',
        'position' => 'position',
        'country' => 'country',
        'city' => 'city',
        'address' => 'address',
        'project_type' => 'project_type',
        'project_size' => 'project_size',
        'estimated_budget' => 'estimated_budget',
        'expected_close_date' => 'expected_close_date',
        'source' => 'source',
        'source_details' => 'source_details',
        'status' => 'status',
        'priority' => 'priority',
        'notes' => 'notes',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة Lead')
                ->visible(fn () => auth()->user()?->can('leads.create')),

            Actions\Action::make('import_leads')
                ->label('استيراد Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => auth()->user()?->can('leads.create'))
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel أو CSV')
                        ->helperText('يجب أن يكون ملف XLSX أو CSV ويحتوي على صف أول بأسماء الأعمدة')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->disk('local')
                        ->directory('lead-imports')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->processImport($data['file']);
                }),

            ExportAction::make()
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->exporter(LeadExporter::class)
                ->visible(fn () => auth()->user()?->can('leads.view_any')),

            Actions\Action::make('download_template')
                ->label('نموذج Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('leads.create'))
                ->action(fn () => $this->downloadTemplate()),
        ];
    }

    public function getTitle(): string
    {
        return 'العملاء المحتملين';
    }

    /**
     * Process the uploaded Excel/CSV file directly.
     * Reads headers from row 1, maps to Lead fields, creates/updates leads.
     */
    protected function processImport(string $storagePath): void
    {
        $fullPath = storage_path('app/'.ltrim($storagePath, '/'));

        if (! file_exists($fullPath)) {
            Notification::make()->title('الملف غير موجود')->danger()->send();

            return;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        try {
            $rows = $extension === 'csv'
                ? $this->readCsv($fullPath)
                : $this->readXlsx($fullPath);
        } catch (\Throwable $e) {
            Notification::make()->title('خطأ في قراءة الملف')->body($e->getMessage())->danger()->send();

            return;
        }

        if (count($rows) < 2) {
            Notification::make()->title('الملف فارغ أو لا يحتوي إلا على الـ headers')->warning()->send();

            return;
        }

        // Row 0 = headers
        $headerRow = array_map('trim', $rows[0]);
        $fieldMap = $this->buildFieldMap($headerRow);

        if (! isset($fieldMap['name']) || ! isset($fieldMap['phone'])) {
            Notification::make()
                ->title('الملف لا يحتوي على الأعمدة المطلوبة')
                ->body('يجب أن يحتوي الملف على عمود "الاسم" و "رقم الهاتف" على الأقل')
                ->danger()
                ->send();

            return;
        }

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach (array_slice($rows, 1) as $idx => $row) {
            if (empty(array_filter($row))) {
                continue; // skip blank rows
            }

            try {
                $mapped = $this->mapRow($row, $fieldMap);

                if (empty($mapped['name']) || empty($mapped['phone'])) {
                    $failed++;

                    continue;
                }

                $mapped = $this->normalizeRow($mapped);

                $existing = Lead::withTrashed()->where('phone', $mapped['phone'])->first();

                if ($existing) {
                    $existing->restore();
                    $existing->update($mapped);
                    $updated++;
                } else {
                    $mapped['created_by'] = auth()->id();
                    Lead::create($mapped);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Lead import row '.($idx + 2).' failed: '.$e->getMessage());
            }
        }

        $message = "تم الاستيراد: {$created} جديد، {$updated} محدّث";
        if ($failed > 0) {
            $message .= "، {$failed} صف فاشل";
        }

        Notification::make()
            ->title($failed === 0 ? 'اكتمل الاستيراد' : 'اكتمل الاستيراد مع أخطاء')
            ->body($message)
            ->color($failed === 0 ? 'success' : 'warning')
            ->send();

        // Clean up
        @unlink($fullPath);
    }

    /**
     * Build a field map: [ field_name => column_index ] from header row.
     */
    protected function buildFieldMap(array $headers): array
    {
        $fieldMap = [];
        foreach ($headers as $colIndex => $header) {
            $header = trim($header);
            if (isset(static::$headerMap[$header])) {
                $fieldMap[static::$headerMap[$header]] = $colIndex;
            }
        }

        return $fieldMap;
    }

    /**
     * Map a data row to an associative array of field => value.
     */
    protected function mapRow(array $row, array $fieldMap): array
    {
        $data = [];
        foreach ($fieldMap as $field => $colIndex) {
            $data[$field] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
        }

        return $data;
    }

    /**
     * Normalize values: enums, dates, numbers.
     */
    protected function normalizeRow(array $data): array
    {
        // Normalize enums to valid keys
        if (isset($data['source'])) {
            $data['source'] = $this->normalizeEnum($data['source'], Lead::SOURCES, 'other');
        }
        if (isset($data['status'])) {
            $data['status'] = $this->normalizeEnum($data['status'], Lead::STATUSES, 'new');
        }
        if (isset($data['priority'])) {
            $data['priority'] = $this->normalizeEnum($data['priority'], Lead::PRIORITIES, 'medium');
        }

        // Normalize budget
        if (isset($data['estimated_budget'])) {
            $val = preg_replace('/[^\d.]/', '', $data['estimated_budget']);
            $data['estimated_budget'] = $val !== '' ? (float) $val : null;
        }

        // Normalize date
        if (isset($data['expected_close_date']) && $data['expected_close_date'] !== '') {
            try {
                $data['expected_close_date'] = Carbon::parse($data['expected_close_date'])->toDateString();
            } catch (\Throwable) {
                $data['expected_close_date'] = null;
            }
        } else {
            $data['expected_close_date'] = null;
        }

        // Defaults
        $data['source'] ??= 'other';
        $data['status'] ??= 'new';
        $data['priority'] ??= 'medium';

        return $data;
    }

    protected function normalizeEnum(string $value, array $map, string $default): string
    {
        $value = trim($value);
        if ($value === '') {
            return $default;
        }
        if (array_key_exists($value, $map)) {
            return $value;
        }
        $flipped = array_flip($map);

        return $flipped[$value] ?? $default;
    }

    // ──────────────────────────── File Readers ────────────────────────────

    protected function readXlsx(string $path): array
    {
        $reader = new XlsxReader;
        $reader->open($path);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $rows[] = array_map(fn ($c) => $c->getValue(), $cells);
            }
            break; // first sheet only
        }

        $reader->close();

        return $rows;
    }

    protected function readCsv(string $path): array
    {
        $reader = new CsvReader;
        $reader->open($path);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $rows[] = array_map(fn ($c) => $c->getValue(), $cells);
            }
            break;
        }

        $reader->close();

        return $rows;
    }

    // ──────────────────────────── Template Download ───────────────────────

    protected function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'الاسم*', 'رقم الهاتف*', 'رقم الواتساب', 'البريد الإلكتروني',
            'الشركة / المزرعة', 'المنصب', 'الدولة', 'المدينة', 'العنوان',
            'نوع المشروع', 'حجم المشروع', 'الميزانية المتوقعة',
            'تاريخ الإغلاق المتوقع (YYYY-MM-DD)',
            'المصدر (facebook/whatsapp/instagram/website/referral/walk_in/phone_call/exhibition/cold_call/other)',
            'تفاصيل المصدر',
            'الحالة (new/contacted/qualified/opportunity/won/lost)',
            'الأولوية (low/medium/high/urgent)',
            'ملاحظات',
        ];

        $exampleRow = [
            'أحمد محمد علي', '01012345678', '01012345678', 'ahmed@example.com',
            'مزرعة الأمل', 'صاحب المزرعة', 'Egypt', 'القاهرة', '10 شارع النيل',
            'تسمين', '50,000 طائر', '5000000', '2026-08-01',
            'whatsapp', 'إعلان فيسبوك يناير 2026', 'new', 'medium', 'عميل جاد ومهتم بالتوسع',
        ];

        return response()->streamDownload(function () use ($headers, $exampleRow) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));
            $writer->addRow(Row::fromValues($exampleRow));
            $writer->close();
        }, 'leads_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
