<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationSection extends Model
{
    use HasFactory;

    protected $table = 'quotation_sections';

    protected $fillable = [
        'code', 'title_ar', 'title_en', 'category',
        'content_ar', 'content_en',
        'default_images', 'sort_order',
        'applicable_quotation_types', 'is_active',
    ];

    protected $casts = [
        'default_images' => 'array',
        'applicable_quotation_types' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const CATEGORIES = [
        'technical' => 'تقني',
        'civil' => 'مدني',
        'electrical' => 'كهرباء',
        'cooling' => 'تبريد',
        'ventilation' => 'تهوية',
        'feeding' => 'تغذية',
        'water' => 'مياه',
        'cages' => 'بطاريات',
        'cleaning' => 'تنظيف',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QuotationSectionAttachment::class);
    }

    public function quotations(): BelongsToMany
    {
        return $this->belongsToMany(Quotation::class, 'quotation_section_attachments')
            ->withPivot([
                'content_override_ar',
                'content_override_en',
                'custom_images',
                'sort_order',
                'is_visible',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
