<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'name_en', 'national_id', 'nationality',
        'phone', 'phone_alt', 'email', 'whatsapp',
        'address', 'city', 'country',
        'tax_number', 'commercial_register',
        'type', 'status',
        'attachments', 'notes',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    /**
     * توليد كود العميل تلقائياً
     */
    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $lastId = static::withTrashed()->max('id') ?? 0;
                $customer->code = 'CUST-'.str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function getTotalContractsValueAttribute(): float
    {
        return $this->contracts()->sum('total_value');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->contracts()->withSum('payments', 'paid_amount')->get()
            ->sum('payments_sum_paid_amount');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
