<?php

namespace App\Filament\Resources\PoultryQuotationResource\Pages;

use App\Filament\Resources\PoultryQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPoultryQuotation extends EditRecord
{
    protected static string $resource = PoultryQuotationResource::class;

    protected function afterSave(): void
    {
        try {
            $this->record->refresh();
            $this->record->autoCompute();
            $this->record->saveQuietly();
        } catch (\Throwable) {
            // incomplete dimensions — preserved as-is
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('عرض'),
            Actions\DeleteAction::make()
                ->label('نقل إلى السلة')
                ->modalHeading('نقل العرض إلى سلة المحذوفات')
                ->modalDescription('يقدر يترجع من قائمة الحاسبة خلال '.config('trash.retention_days', 30).' يوم.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الحساب بنجاح';
    }
}
