<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return;
        }

        RoleResource::syncPermissionGroups($this->record, $this->data);
    }
}
