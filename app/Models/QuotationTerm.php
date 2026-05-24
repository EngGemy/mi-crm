<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationTerm extends Model
{
    use HasFactory;

    protected $table = 'quotation_terms';

    protected $fillable = [
        'code', 'title_ar', 'title_en',
        'content_ar', 'content_en',
        'variables',
        'is_required', 'is_default',
        'sort_order',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_required' => 'boolean',
        'is_default' => 'boolean',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(QuotationTermAttachment::class);
    }

    public function quotations(): BelongsToMany
    {
        return $this->belongsToMany(Quotation::class, 'quotation_term_attachments')
            ->withPivot([
                'content_override',
                'variables_values',
                'sort_order',
                'is_visible',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * استخراج المتغيرات من النص {{var_name}}
     */
    public function extractVariableNames(): array
    {
        preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/', $this->content_ar ?? '', $matches);

        return array_unique($matches[1] ?? []);
    }
}
