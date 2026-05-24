<?php

namespace App\Filament\Resources\QuotationTermResource\Pages;

use App\Filament\Resources\QuotationTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotationTerms extends ListRecords
{
    protected static string $resource = QuotationTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ بند جديد'),
        ];
    }
}
