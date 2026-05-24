<?php

namespace App\Filament\Resources\QuotationTypeResource\Pages;

use App\Filament\Resources\QuotationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotationTypes extends ListRecords
{
    protected static string $resource = QuotationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ نوع جديد'),
        ];
    }
}
