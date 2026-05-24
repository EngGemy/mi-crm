<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Services\QuotationCalculator;
use App\Services\QuotationImageGenerator;
use App\Services\QuotationSharingService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return QuotationCalculator::mergeCalculatedTotalsIntoFormData($data);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation(fn (): bool => (float) ($this->data['total_amount'] ?? 0) > 1_000_000)
            ->modalHeading('تأكيد حفظ عرض بقيمة كبيرة')
            ->modalDescription(fn (): string => 'الإجمالي: '.number_format((float) ($this->data['total_amount'] ?? 0), 2).' ج.م — هل تريد المتابعة؟');
    }

    protected function getSavedNotification(): ?Notification
    {
        $total = (float) ($this->record->total_amount ?? 0);

        return Notification::make()
            ->title('تم حفظ عرض السعر')
            ->body('الإجمالي: '.number_format($total, 2).' ج.م')
            ->success()
            ->duration(5000);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('معاينة')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('quotations.preview', $this->record))
                ->openUrlInNewTab(),

            Action::make('imagePreview')
                ->label('عرض كصورة')
                ->icon('heroicon-o-photo')
                ->color('success')
                ->action(function () {
                    $img = app(QuotationImageGenerator::class);
                    $img->generate($this->record);
                    $url = $img->getPublicUrl($this->record);
                    Notification::make()
                        ->title('تم توليد الصورة')
                        ->body("الرابط: {$url}")
                        ->success()->persistent()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('open')->label('فتح الصورة')->url($url, true),
                        ])->send();
                }),

            Action::make('shareWhatsApp')
                ->label('شارك واتساب')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn () => app(QuotationSharingService::class)->getWhatsAppShareLink($this->record))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make()->label('حذف'),
            Action::make('duplicate')
                ->label('نسخة جديدة')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;
                    $new = $record->replicate([
                        'quotation_number', 'status', 'sent_at', 'approved_at',
                        'rejected_at', 'converted_at', 'contract_id',
                    ]);
                    $new->status = 'draft';
                    $new->quotation_date = now();
                    $new->valid_until = now()->addDays($record->validity_period_days ?? 7);
                    $new->parent_quotation_id = $record->id;
                    $new->revision_number = ($record->revisions()->max('revision_number') ?? 0) + 1;
                    $new->save();

                    // نسخ الأقسام
                    foreach ($record->sectionAttachments as $att) {
                        $new->sectionAttachments()->create($att->toArray());
                    }
                    // نسخ البنود
                    foreach ($record->termAttachments as $att) {
                        $new->termAttachments()->create($att->toArray());
                    }
                    // نسخ الـ items
                    foreach ($record->items as $item) {
                        $new->items()->create($item->toArray());
                    }

                    $this->redirect(QuotationResource::getUrl('edit', ['record' => $new]));
                }),
        ];
    }
}
