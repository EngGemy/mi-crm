<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Lookup extends Model
{
    protected $fillable = [
        'type', 'code', 'label_ar', 'label_en', 'value', 'sort_order', 'is_active', 'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
        'sort_order' => 'integer',
    ];

    public const TYPE_QUOTE_TYPE = 'quote_type';

    public const TYPE_MANURE_MOTOR_COUNT = 'manure_motor_count';

    public const TYPE_MOTOR_POWER = 'motor_power';

    public const TYPE_BELTS_PER_LINE = 'belts_per_line';

    public const TYPE_INNER_BELT_LENGTH = 'inner_belt_length';

    public const TYPE_OUTER_BELT_LENGTH = 'outer_belt_length';

    public const TYPE_SILO_CAPACITY = 'silo_capacity';

    public const TYPE_COUNTRY = 'country';

    public const TYPE_LOCATION = 'location';

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_QUOTE_TYPE => 'نوع عرض السعر',
            self::TYPE_MANURE_MOTOR_COUNT => 'عدد مواتير دولاب السبلة',
            self::TYPE_MOTOR_POWER => 'قدرة الماتور',
            self::TYPE_BELTS_PER_LINE => 'عدد السيور في الخط',
            self::TYPE_INNER_BELT_LENGTH => 'طول السير الداخلي',
            self::TYPE_OUTER_BELT_LENGTH => 'طول السير الخارجي',
            self::TYPE_SILO_CAPACITY => 'سعة السايلو',
            self::TYPE_COUNTRY => 'الدولة',
            self::TYPE_LOCATION => 'المحافظة / المنطقة',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->active()->orderBy('sort_order')->orderBy('id');
    }

    /** @return array<int|string, string> */
    public static function options(string $type): array
    {
        return static::ofType($type)
            ->pluck('label_ar', 'id')
            ->all();
    }

    public static function defaultId(string $type): ?int
    {
        return static::ofType($type)->value('id');
    }

    /** @return Collection<int, Lookup> */
    public static function forType(string $type): Collection
    {
        return static::ofType($type)->get();
    }
}
