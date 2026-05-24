<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationTechnicalSpec extends Model
{
    use HasFactory;

    protected $table = 'quotation_technical_specs';

    protected $fillable = [
        'quotation_id', 'spec_type',
        'title_ar', 'title_en',
        'data', 'sort_order',
    ];

    protected $casts = [
        'data' => 'array',
        'sort_order' => 'integer',
    ];

    public const SPEC_TYPES = [
        'hall_dimensions' => 'أبعاد العنبر',
        'battery_specs' => 'مواصفات البطاريات',
        'cage_specs' => 'مواصفات الأقفاص',
        'care_per_weight' => 'العناية حسب الوزن',
        'custom' => 'مخصص',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function getSpecTypeLabelAttribute(): string
    {
        return self::SPEC_TYPES[$this->spec_type] ?? $this->spec_type;
    }
}
