<?php

namespace App\Filament\Resources\SettingHistoryResource\Pages;

use App\Filament\Resources\SettingHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListSettingHistories extends ListRecords
{
    protected static string $resource = SettingHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
