<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return;
        }

        RoleResource::syncPermissionGroups($this->record, $this->data);
    }
}
