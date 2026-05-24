<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageLibrary extends Model
{
    use HasFactory;

    protected $table = 'image_library';

    protected $fillable = [
        'code', 'title_ar', 'title_en', 'category',
        'file_path', 'file_size', 'width', 'height',
        'alt_text_ar', 'alt_text_en', 'tags',
        'usage_count', 'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'usage_count' => 'integer',
        'tags' => 'array',
    ];

    public const CATEGORIES = [
        'steel_work' => 'أعمال معدنية',
        'cooling' => 'تبريد',
        'ventilation' => 'تهوية',
        'feeding' => 'تغذية',
        'water' => 'مياه',
        'cages' => 'بطاريات',
        'cleaning' => 'تنظيف',
        'civil' => 'أعمال مدنية',
        'electrical' => 'كهرباء',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function quotationImages(): HasMany
    {
        return $this->hasMany(QuotationImage::class, 'image_library_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
