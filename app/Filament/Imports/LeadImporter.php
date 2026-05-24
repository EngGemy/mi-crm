<?php

namespace App\Filament\Imports;

use App\Models\Lead;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class LeadImporter extends Importer
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('الاسم')
                ->requiredMapping()
                ->rules(['required', 'max:200']),

            ImportColumn::make('phone')
                ->label('رقم الهاتف')
                ->requiredMapping()
                ->rules(['required', 'max:50']),

            ImportColumn::make('whatsapp')
                ->label('رقم الواتساب')
                ->rules(['nullable', 'max:50']),

            ImportColumn::make('email')
                ->label('البريد الإلكتروني')
                ->rules(['nullable', 'email', 'max:200']),

            ImportColumn::make('company')
                ->label('الشركة / المزرعة')
                ->rules(['nullable', 'max:200']),

            ImportColumn::make('position')
                ->label('المنصب')
                ->rules(['nullable', 'max:100']),

            ImportColumn::make('country')
                ->label('الدولة')
                ->rules(['nullable', 'max:100']),

            ImportColumn::make('city')
                ->label('المدينة')
                ->rules(['nullable', 'max:100']),

            ImportColumn::make('address')
                ->label('العنوان')
                ->rules(['nullable', 'max:500']),

            ImportColumn::make('project_type')
                ->label('نوع المشروع')
                ->rules(['nullable', 'max:100']),

            ImportColumn::make('project_size')
                ->label('حجم المشروع')
                ->rules(['nullable', 'max:200']),

            ImportColumn::make('estimated_budget')
                ->label('الميزانية المتوقعة')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('expected_close_date')
                ->label('تاريخ الإغلاق المتوقع')
                ->rules(['nullable', 'date']),

            ImportColumn::make('source')
                ->label('المصدر')
                ->rules(['nullable'])
                ->fillRecordUsing(static function (Lead $record, string $state): void {
                    $record->source = self::normalizeEnum($state, Lead::SOURCES, 'other');
                }),

            ImportColumn::make('source_details')
                ->label('تفاصيل المصدر')
                ->rules(['nullable', 'max:500']),

            ImportColumn::make('status')
                ->label('الحالة')
                ->rules(['nullable'])
                ->fillRecordUsing(static function (Lead $record, string $state): void {
                    $record->status = self::normalizeEnum($state, Lead::STATUSES, 'new');
                }),

            ImportColumn::make('priority')
                ->label('الأولوية')
                ->rules(['nullable'])
                ->fillRecordUsing(static function (Lead $record, string $state): void {
                    $record->priority = self::normalizeEnum($state, Lead::PRIORITIES, 'medium');
                }),

            ImportColumn::make('notes')
                ->label('ملاحظات')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Lead
    {
        // منع التكرار: لو وُجد lead بنفس الهاتف حدّثه
        $existing = Lead::withTrashed()->where('phone', $this->data['phone'])->first();

        if ($existing) {
            $existing->restore();

            return $existing;
        }

        return new Lead;
    }

    protected function beforeFill(): void
    {
        $this->data['created_by'] ??= auth()->id();
        $this->data['source'] = blank($this->data['source'] ?? null) ? 'other' : $this->data['source'];
        $this->data['status'] = blank($this->data['status'] ?? null) ? 'new' : $this->data['status'];
        $this->data['priority'] = blank($this->data['priority'] ?? null) ? 'medium' : $this->data['priority'];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'اكتمل الاستيراد: '.number_format($import->successful_rows).' صف ناجح';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ', '.number_format($failedRowsCount).' صف فاشل.';
        }

        return $body;
    }

    /** Normalizes Arabic label or key to enum key; falls back to $default on unknown. */
    public static function normalizeEnum(string $value, array $map, string $default): string
    {
        $value = trim($value);

        if ($value === '') {
            return $default;
        }

        if (array_key_exists($value, $map)) {
            return $value;
        }

        $flipped = array_flip($map);
        if (isset($flipped[$value])) {
            return $flipped[$value];
        }

        return $default;
    }
}
