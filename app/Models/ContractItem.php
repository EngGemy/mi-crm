<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMoneyAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    use HasFactory, NormalizesMoneyAttributes;

    protected $fillable = [
        'contract_id', 'product_id', 'section', 'description',
        'technical_specs', 'quantity', 'unit',
        'unit_price', 'discount_percentage', 'total_price',
        'is_taxable', 'sort_order', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    public const SECTIONS = [
        'cages' => 'البطاريات',
        'construction' => 'الإنشاءات',
        'electricity' => 'الكهرباء',
        'plumbing' => 'السباكة',
        'ventilation' => 'التهوية',
        'control' => 'التحكم',
        'cooling' => 'التبريد',
        'heating' => 'التدفئة',
        'isolation' => 'العزل',
        'fire_system' => 'الإطفاء',
        'generator' => 'الجنريتور',
        'spare_parts' => 'قطع الغيار',
        'services' => 'الخدمات',
    ];

    protected static function booted(): void
    {
        static::saving(function (ContractItem $item) {
            $afterDiscount = (float) $item->unit_price *
                (1 - ((float) $item->discount_percentage / 100));
            $item->total_price = (float) $item->quantity * $afterDiscount;
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getSectionLabelAttribute(): string
    {
        return self::SECTIONS[$this->section] ?? $this->section;
    }

    protected function unitPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function totalPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }
}
