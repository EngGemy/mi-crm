<?php

namespace App\Filament\Resources\PoultryQuotationResource\Pages;

use App\Filament\Resources\PoultryQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPoultryQuotations extends ListRecords
{
    protected static string $resource = PoultryQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('حساب سعر جديد'),
        ];
    }
}
