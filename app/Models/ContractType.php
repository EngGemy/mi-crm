<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'name_en', 'description',
        'icon', 'color',
        'default_milestones', 'default_clauses', 'payment_schedule_default',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_milestones' => 'array',
        'default_clauses' => 'array',
        'payment_schedule_default' => 'array',
        'is_active' => 'boolean',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
