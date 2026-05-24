<?php

namespace App\Filament\Resources\ImageLibraryResource\Pages;

use App\Filament\Resources\ImageLibraryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImageLibrary extends EditRecord
{
    protected static string $resource = ImageLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
