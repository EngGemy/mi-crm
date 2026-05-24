<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationImage extends Model
{
    use HasFactory;

    protected $table = 'quotation_images';

    protected $fillable = [
        'quotation_id', 'image_library_id', 'file_path',
        'position', 'section_id',
        'caption_ar', 'caption_en', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public const POSITIONS = [
        'cover' => 'غلاف',
        'section' => 'قسم',
        'terms' => 'بنود',
        'footer' => 'تذييل',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function imageLibrary(): BelongsTo
    {
        return $this->belongsTo(ImageLibrary::class, 'image_library_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuotationSection::class, 'section_id');
    }

    public function getPositionLabelAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? $this->position;
    }
}
