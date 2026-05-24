<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Services\ContractCalculator;
use App\Services\PaymentScheduler;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge($data, ContractCalculator::calculateContract($data));
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation(fn (): bool => ContractResource::estimateTotalValueFromData($this->data) > 1_000_000)
            ->modalHeading('تأكيد إنشاء عقد بقيمة كبيرة')
            ->modalDescription(fn (): string => 'قيمة العقد (تقدير): '.number_format(ContractResource::estimateTotalValueFromData($this->data), 2).' ج.م — هل تريد المتابعة؟');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    /**
     * بعد حفظ العقد، يتم توليد جدول الدفعات تلقائياً
     */
    protected function afterCreate(): void
    {
        try {
            app(PaymentScheduler::class)->generateForContract($this->record);

            $total = (float) ($this->record->total_value ?? 0);

            Notification::make()
                ->title('تم إنشاء العقد')
                ->body('الإجمالي: '.number_format($total, 2).' ج.م — تم توليد جدول الدفعات والمراحل تلقائياً')
                ->success()
                ->duration(5000)
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('تم إنشاء العقد')
                ->body('لكن حدث خطأ في توليد الدفعات: '.$e->getMessage())
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
