<?php

namespace App\Filament\Resources\ExhibitionResource\Pages;

use App\Filament\Resources\ExhibitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExhibition extends EditRecord
{
    protected static string $resource = ExhibitionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('حذف')];
    }
}
