<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getSavedNotification(): ?Notification
    {
        $paid = (float) ($this->record->paid_amount ?? 0);
        $expected = (float) ($this->record->expected_amount ?? 0);

        return Notification::make()
            ->title('تم حفظ الدفعة')
            ->body(
                'المدفوع: '.number_format($paid, 2).' ج.م'
                .($expected > 0 ? ' — المستحق: '.number_format($expected, 2).' ج.م' : '')
            )
            ->success()
            ->duration(5000);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('حذف')];
    }
}
