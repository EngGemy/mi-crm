<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'value', 'type', 'category',
        'label_ar', 'label_en', 'description',
        'is_public', 'is_required', 'sort_order',
        'validation_rules', 'options', 'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'validation_rules' => 'array',
        'options' => 'array',
    ];

    public const TYPES = [
        'string' => 'نص قصير',
        'text' => 'نص طويل',
        'integer' => 'رقم صحيح',
        'decimal' => 'رقم عشري',
        'boolean' => 'نعم/لا',
        'json' => 'JSON',
        'array' => 'مصفوفة',
        'date' => 'تاريخ',
        'image' => 'صورة',
        'file' => 'ملف',
        'color' => 'لون',
    ];

    public const CATEGORIES = [
        'company' => 'بيانات الشركة',
        'contact' => 'بيانات الاتصال',
        'legal' => 'قانوني وضريبي',
        'banking' => 'حسابات بنكية',
        'branding' => 'الهوية البصرية',
        'defaults' => 'القيم الافتراضية',
        'pdf' => 'إعدادات PDF',
        'poultry_pricing' => 'تسعير عنابر الدواجن',
        'tax' => 'الضريبة والجمارك',
        'finance' => 'الإعدادات المالية',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SettingHistory::class)->orderBy('created_at', 'desc');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
