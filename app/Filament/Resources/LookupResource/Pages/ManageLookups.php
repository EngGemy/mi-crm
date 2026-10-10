<?php

namespace App\Filament\Resources\LookupResource\Pages;

use App\Filament\Resources\LookupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageLookups extends ManageRecords
{
    protected static string $resource = LookupResource::class;

    public function getSubheading(): string | Htmlable | null
    {
        return 'كل مجموعة هي قائمة تظهر في عرض السعر. النجمة «بارز» هي الخيار الذي يُختار تلقائياً. من الصف تضغط «اجعلها الافتراضية» بدون فتح التعديل.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة خيار'),
        ];
    }
}
