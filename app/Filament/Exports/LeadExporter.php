<?php

namespace App\Filament\Exports;

use App\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('lead_number')->label('رقم اللـ Lead'),
            ExportColumn::make('name')->label('الاسم'),
            ExportColumn::make('phone')->label('رقم الهاتف'),
            ExportColumn::make('whatsapp')->label('واتساب'),
            ExportColumn::make('email')->label('البريد الإلكتروني'),
            ExportColumn::make('company')->label('الشركة / المزرعة'),
            ExportColumn::make('position')->label('المنصب'),
            ExportColumn::make('country')->label('الدولة'),
            ExportColumn::make('city')->label('المدينة'),
            ExportColumn::make('address')->label('العنوان'),
            ExportColumn::make('project_type')->label('نوع المشروع'),
            ExportColumn::make('project_size')->label('حجم المشروع'),
            ExportColumn::make('estimated_budget')->label('الميزانية المتوقعة'),
            ExportColumn::make('expected_close_date')->label('تاريخ الإغلاق المتوقع'),
            ExportColumn::make('source')
                ->label('المصدر')
                ->formatStateUsing(fn ($state) => Lead::SOURCES[$state] ?? $state),
            ExportColumn::make('source_details')->label('تفاصيل المصدر'),
            ExportColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn ($state) => Lead::STATUSES[$state] ?? $state),
            ExportColumn::make('priority')
                ->label('الأولوية')
                ->formatStateUsing(fn ($state) => Lead::PRIORITIES[$state] ?? $state),
            ExportColumn::make('score')->label('الاحتمالية %'),
            ExportColumn::make('assignedUser.name')->label('المسؤول'),
            ExportColumn::make('last_contact_at')->label('آخر تواصل'),
            ExportColumn::make('next_followup_at')->label('المتابعة القادمة'),
            ExportColumn::make('notes')->label('ملاحظات'),
            ExportColumn::make('created_at')->label('تاريخ الإضافة'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'اكتمل التصدير: '.number_format($export->successful_rows).' صف';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ', '.number_format($failedRowsCount).' صف فاشل.';
        }

        return $body;
    }
}
