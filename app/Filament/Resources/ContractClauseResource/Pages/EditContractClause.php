<?php

namespace App\Filament\Resources\ContractClauseResource\Pages;

use App\Filament\Resources\ContractClauseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractClause extends EditRecord
{
    protected static string $resource = ContractClauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
