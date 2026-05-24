<?php

namespace App\Filament\Resources\QuotationSectionResource\Pages;

use App\Filament\Resources\QuotationSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotationSection extends EditRecord
{
    protected static string $resource = QuotationSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
