<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PermissionGate
{
    public static function allows(string ...$permissions): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function ownOnly(string $viewAny): bool
    {
        return ! self::allows($viewAny);
    }

    public static function scopeLeads(Builder $query): Builder
    {
        $user = auth()->user();
        if ($user instanceof User && self::ownOnly('leads.view_any') && self::allows('leads.view_own')) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query;
    }
}
