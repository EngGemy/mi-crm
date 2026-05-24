<?php

namespace App\Filament\Resources\PoultryQuotationResource\Pages;

use App\Filament\Resources\PoultryQuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePoultryQuotation extends CreateRecord
{
    protected static string $resource = PoultryQuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم حساب السعر بنجاح';
    }
}
