<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label_ar',
        'label_en',
        'value',
        'unit',
        'category',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getValue(string $key, $default = 0): string
    {
        $param = self::where('key', $key)->first();

        return $param ? (string) $param->value : (string) $default;
    }

    public static function toCalculatorArray(): array
    {
        return self::active()
            ->get()
            ->mapWithKeys(fn ($p) => [$p->key => (string) $p->value])
            ->toArray();
    }
}
