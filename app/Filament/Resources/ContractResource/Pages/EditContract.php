<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Services\ContractCalculator;
use App\Services\PaymentScheduler;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return array_merge($data, ContractCalculator::calculateContract($data));
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation(fn (): bool => (float) ($this->record->total_value ?? 0) > 1_000_000
                || ContractResource::estimateTotalValueFromData($this->data) > 1_000_000)
            ->modalHeading('تأكيد حفظ عقد بقيمة كبيرة')
            ->modalDescription(function (): string {
                $t = max(
                    (float) ($this->record->total_value ?? 0),
                    ContractResource::estimateTotalValueFromData($this->data)
                );

                return 'قيمة العقد (تقدير): '.number_format($t, 2).' ج.م — هل تريد المتابعة؟';
            });
    }

    protected function getSavedNotification(): ?Notification
    {
        $total = (float) ($this->record->total_value ?? 0);

        return Notification::make()
            ->title('تم حفظ العقد')
            ->body('الإجمالي: '.number_format($total, 2).' ج.م')
            ->success()
            ->duration(5000);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('📄 تحميل PDF')
                ->color('success')
                ->url(fn () => route('contracts.download', $this->record)),

            Action::make('regeneratePayments')
                ->label('🔄 إعادة توليد الدفعات')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('سيتم حذف جدول الدفعات الحالي وإنشاء واحد جديد بناءً على القيمة الحالية للعقد.')
                ->action(function () {
                    app(PaymentScheduler::class)->generateForContract($this->record);
                    Notification::make()
                        ->title('تم إعادة توليد جدول الدفعات')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
