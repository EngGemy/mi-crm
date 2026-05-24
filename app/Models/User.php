<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar_url', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * يحدد لو المستخدم يقدر يدخل الـ Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole([
            'super_admin', 'admin', 'sales_manager', 'sales_rep', 'accountant',
        ]);
    }

    /**
     * Helper: هل هذا المستخدم Sales Rep؟
     */
    public function isSalesRep(): bool
    {
        return $this->hasRole('sales_rep');
    }

    /**
     * Helper: هل هذا المستخدم Super Admin؟
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
