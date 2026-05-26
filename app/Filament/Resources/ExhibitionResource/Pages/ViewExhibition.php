<?php

namespace App\Filament\Resources\ExhibitionResource\Pages;

use App\Filament\Resources\ExhibitionResource;
use App\Models\Exhibition;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExhibition extends ViewRecord
{
    protected static string $resource = ExhibitionResource::class;

    protected static string $view = 'filament.pages.exhibition-view';

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->label('تعديل')];
    }

    public function getViewData(): array
    {
        /** @var Exhibition $record */
        $record = $this->record;

        return [
            'leadsCount' => $record->leadsCount(),
            'quotationsCount' => $record->quotationsCount(),
            'contractsCount' => $record->contractsCount(),
            'contractsValue' => $record->contractsValue(),
            'roiPercentage' => $record->roiPercentage(),
            'conversionRate' => $record->conversionRate(),
            'leads' => $record->leads()->with('assignedUser')->latest()->get(),
        ];
    }
}
