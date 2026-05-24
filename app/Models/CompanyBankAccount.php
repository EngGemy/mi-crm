<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBankAccount extends Model
{
    use HasFactory;

    protected $table = 'company_bank_accounts';

    protected $fillable = [
        'bank_name_ar', 'bank_name_en',
        'account_name_ar', 'account_name_en',
        'account_number', 'iban', 'swift_code',
        'currency', 'branch',
        'is_default', 'is_active', 'sort_order', 'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
