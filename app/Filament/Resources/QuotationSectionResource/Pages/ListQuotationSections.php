<?php

namespace App\Filament\Resources\QuotationSectionResource\Pages;

use App\Filament\Resources\QuotationSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotationSections extends ListRecords
{
    protected static string $resource = QuotationSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ قسم جديد'),
        ];
    }
}
