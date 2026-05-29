<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return 'إضافة عميل محتمل';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($status = request()->query('status')) {
            $data['status'] = $status;
        }
        return $data;
    }
}
