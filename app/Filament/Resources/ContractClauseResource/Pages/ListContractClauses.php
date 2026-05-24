<?php

namespace App\Filament\Resources\ContractClauseResource\Pages;

use App\Filament\Resources\ContractClauseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractClauses extends ListRecords
{
    protected static string $resource = ContractClauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ بند جديد'),
        ];
    }
}
