<?php

namespace App\Filament\Resources\QuotationTypeResource\Pages;

use App\Filament\Resources\QuotationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotationType extends EditRecord
{
    protected static string $resource = QuotationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
