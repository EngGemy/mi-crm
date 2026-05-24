<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationSectionAttachment extends Model
{
    use HasFactory;

    protected $table = 'quotation_section_attachments';

    protected $fillable = [
        'quotation_id', 'quotation_section_id',
        'content_override_ar', 'content_override_en',
        'custom_images', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'custom_images' => 'array',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuotationSection::class, 'quotation_section_id');
    }
}
