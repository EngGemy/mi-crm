<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['quotations.view_any', 'quotations.view_own']);
    }

    public function view(User $user, Quotation $quotation): bool
    {
        if ($user->can('quotations.view_any')) {
            return true;
        }

        if ($user->can('quotations.view_own')) {
            return $quotation->created_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        if ($user->can('quotations.update')) {
            return true;
        }

        if ($user->can('quotations.update_own')) {
            return $quotation->created_by === $user->id
                && in_array($quotation->status, ['draft', 'sent']);
        }

        return false;
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete');
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.send')
            && in_array($quotation->status, ['draft']);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.approve')
            && $quotation->status === 'sent';
    }

    public function convert(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.convert')
            && $quotation->status === 'approved'
            && ! $quotation->contract_id;
    }
}
