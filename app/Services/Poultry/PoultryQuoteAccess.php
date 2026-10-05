<?php

namespace App\Services\Poultry;

use App\Models\User;

/**
 * Exhibition visibility. Managers see everything. Each sales rep sees only
 * what the manager turned on for them.
 */
class PoultryQuoteAccess
{
    public static function seesOwnQuotesOnly(?User $user): bool
    {
        if ($user === null || ! $user->hasRole('sales_rep')) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return false;
        }

        return ! self::allows($user, 'view_team');
    }

    public static function allows(?User $user, string $ability): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        $access = $user->exhibitionAccess;

        return match ($ability) {
            'report_daily' => $access?->report_daily ?? true,
            'report_weekly' => $access?->report_weekly ?? true,
            'view_team' => (bool) ($access?->view_team ?? false),
            default => false,
        };
    }
}
