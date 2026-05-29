<?php

namespace App\Filament\Resources\ExhibitionResource\Pages;

use App\Filament\Resources\ExhibitionResource;
use App\Filament\Widgets\ExhibitionStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExhibitions extends ListRecords
{
    protected static string $resource = ExhibitionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('معرض جديد')->icon('heroicon-o-plus')];
    }

    protected function getHeaderWidgets(): array
    {
        return [ExhibitionStatsWidget::class];
    }
}
