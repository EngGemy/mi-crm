<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationType extends Model
{
    use HasFactory;

    protected $table = 'quotation_types';

    protected $fillable = [
        'code', 'name', 'name_en', 'description',
        'icon', 'color',
        'default_sections', 'default_terms',
        'default_payment_schedule', 'template_layout',
        'default_validity_days',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_sections' => 'array',
        'default_terms' => 'array',
        'default_payment_schedule' => 'array',
        'template_layout' => 'array',
        'default_validity_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
