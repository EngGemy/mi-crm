<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMoneyAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, NormalizesMoneyAttributes;

    protected $fillable = [
        'code', 'name', 'name_en', 'category', 'unit',
        'standard_price', 'currency',
        'technical_specs', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const CATEGORIES = [
        'cages' => 'بطاريات',
        'construction' => 'إنشاءات',
        'electricity' => 'كهرباء',
        'plumbing' => 'سباكة',
        'ventilation' => 'تهوية',
        'control' => 'تحكم',
        'cooling' => 'تبريد',
        'heating' => 'تدفئة',
        'feed_system' => 'نظام التغذية',
        'water_system' => 'نظام المياه',
        'manure_system' => 'نظام السبلة',
        'isolation' => 'عزل',
        'fire_system' => 'إطفاء',
        'generator' => 'جنريتور',
        'spare_parts' => 'قطع غيار',
        'services' => 'خدمات',
    ];

    public const UNITS = [
        'piece' => 'قطعة',
        'meter' => 'متر',
        'sqm' => 'م²',
        'cbm' => 'م³',
        'ton' => 'طن',
        'kg' => 'كجم',
        'liter' => 'لتر',
        'set' => 'مجموعة',
        'hour' => 'ساعة',
        'day' => 'يوم',
        'hall' => 'عنبر',
        'bird' => 'طائر',
        'cage' => 'قفص',
        'lot' => 'دفعة',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->code)) {
                $lastId = static::max('id') ?? 0;
                $product->code = 'PROD-'.str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getUnitLabelAttribute(): string
    {
        return self::UNITS[$this->unit] ?? $this->unit;
    }

    protected function standardPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }
}
