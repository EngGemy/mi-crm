<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMoneyAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory, NormalizesMoneyAttributes;

    protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id', 'product_id', 'section_id',
        'description_ar', 'description_en',
        'unit_price', 'unit', 'quantity',
        'discount_percentage', 'total_price',
        'is_taxable', 'tax_label',
        'sort_order', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'discount_percentage' => 'decimal:2',
        'is_taxable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuotationSection::class, 'section_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
