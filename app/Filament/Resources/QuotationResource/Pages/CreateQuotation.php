<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\QuotationSection;
use App\Models\QuotationTerm;
use App\Services\QuotationCalculator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return QuotationCalculator::mergeCalculatedTotalsIntoFormData($data);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation(fn (): bool => (float) ($this->data['total_amount'] ?? 0) > 1_000_000)
            ->modalHeading('تأكيد إنشاء عرض بقيمة كبيرة')
            ->modalDescription(fn (): string => 'الإجمالي: '.number_format((float) ($this->data['total_amount'] ?? 0), 2).' ج.م — هل تريد المتابعة؟');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $total = (float) ($this->record->total_amount ?? 0);

        return Notification::make()
            ->title('تم إنشاء عرض السعر')
            ->body('الإجمالي: '.number_format($total, 2).' ج.م')
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('preview')
                    ->label('فتح المعاينة')
                    ->icon('heroicon-o-eye')
                    ->url(fn () => route('quotations.preview', $this->record))
                    ->openUrlInNewTab(),
                \Filament\Notifications\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record])),
            ])
            ->duration(10000);
    }

    protected function getRedirectUrl(): string
    {
        return route('quotations.preview', $this->record);
    }

    /**
     * بعد حفظ العرض، نملأ الأقسام والبنود الافتراضية لو مش موجودة
     */
    protected function afterCreate(): void
    {
        $quotation = $this->record;
        $type = $quotation->quotationType;

        if (! $type) {
            return;
        }

        // ملء الأقسام الافتراضية
        $sectionIds = $type->default_sections ?? [];
        if (! empty($sectionIds) && $quotation->sectionAttachments()->count() === 0) {
            $sections = QuotationSection::whereIn('id', $sectionIds)->orderBy('sort_order')->get();
            foreach ($sections as $idx => $section) {
                $quotation->sectionAttachments()->create([
                    'quotation_section_id' => $section->id,
                    'sort_order' => $idx,
                    'is_visible' => true,
                ]);
            }
        }

        // ملء البنود الافتراضية
        $termIds = $type->default_terms ?? [];
        if (! empty($termIds) && $quotation->termAttachments()->count() === 0) {
            $terms = QuotationTerm::whereIn('id', $termIds)->orderBy('sort_order')->get();
            foreach ($terms as $idx => $term) {
                $quotation->termAttachments()->create([
                    'quotation_term_id' => $term->id,
                    'sort_order' => $idx,
                    'is_visible' => true,
                ]);
            }
        }
    }
}
