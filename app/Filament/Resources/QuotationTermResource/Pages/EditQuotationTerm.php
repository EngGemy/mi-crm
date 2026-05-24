<?php

namespace App\Filament\Resources\QuotationTermResource\Pages;

use App\Filament\Resources\QuotationTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotationTerm extends EditRecord
{
    protected static string $resource = QuotationTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
