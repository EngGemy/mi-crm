<?php

namespace App\Filament\Resources\PoultryQuotationResource\Pages;

use App\Filament\Resources\PoultryQuotationResource;
use App\Services\Poultry\PoultryWelcomeWhatsApp;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreatePoultryQuotation extends CreateRecord
{
    protected static string $resource = PoultryQuotationResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'مسار عروض الأسعار';
    }

    public function getHeading(): string|Htmlable
    {
        return 'إنشاء عرض سعر';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vat_percentage'] = $data['vat_percentage'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            $this->record->autoCompute();
            $this->record->saveQuietly();
        } catch (\Throwable) {
            // incomplete dimensions — user can fix on edit
        }

        $this->openWelcomeWhatsApp();
    }

    protected function openWelcomeWhatsApp(): void
    {
        try {
            $link = app(PoultryWelcomeWhatsApp::class)->link($this->record);
            if ($link === null) {
                Notification::make()
                    ->title('تم حفظ العرض')
                    ->body('أضف رقم العميل ليُفتح واتساب برسالة الترحيب وعرض السعر.')
                    ->warning()
                    ->send();

                return;
            }

            $this->js('window.open('.json_encode($link).', "_blank")');

            Notification::make()
                ->title('تم فتح واتساب للعميل')
                ->body('رسالة الترحيب وعرض السعر جاهزان. إذا لم تُفتح النافذة، أعد الإرسال من زر واتساب.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم حفظ العرض: '.$this->record->quote_number;
    }

    public function getExtraBodyAttributes(): array
    {
        return ['class' => 'pq-quote-page'];
    }
}
