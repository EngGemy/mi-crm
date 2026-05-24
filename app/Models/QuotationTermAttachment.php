<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationTermAttachment extends Model
{
    use HasFactory;

    protected $table = 'quotation_term_attachments';

    protected $fillable = [
        'quotation_id', 'quotation_term_id',
        'content_override', 'variables_values',
        'sort_order', 'is_visible',
    ];

    protected $casts = [
        'variables_values' => 'array',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(QuotationTerm::class, 'quotation_term_id');
    }
}
