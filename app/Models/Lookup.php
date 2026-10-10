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

    public const TYPE_LAYER_SERVICE = 'layer_service_area';

    public const TYPE_LAYER_BIRDS = 'layer_birds_per_nest';

    public const TYPE_LAYER_SILO_SIDE = 'layer_silo_side';

    public const TYPE_LAYER_INNER_BELT = 'layer_inner_belt';

    public const TYPE_LAYER_WORK_BELT = 'layer_work_belt';

    public const TYPE_LAYER_PEDAL_BELTS = 'layer_pedal_belts';

    public const TYPE_LAYER_CAGE_POWER = 'layer_cage_power';

    public const TYPE_LAYER_EGG_WHEEL = 'layer_egg_wheel';

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
            self::TYPE_LAYER_SERVICE => 'بياض — مساحة الخدمة',
            self::TYPE_LAYER_BIRDS => 'بياض — طيور العش',
            self::TYPE_LAYER_SILO_SIDE => 'بياض — جهة السايلو',
            self::TYPE_LAYER_INNER_BELT => 'بياض — طول السير الداخلي',
            self::TYPE_LAYER_WORK_BELT => 'بياض — طول سير الشغل',
            self::TYPE_LAYER_PEDAL_BELTS => 'بياض — عدد سير الدواسة',
            self::TYPE_LAYER_CAGE_POWER => 'بياض — قدرة القفص',
            self::TYPE_LAYER_EGG_WHEEL => 'بياض — دورات دولاب البيض',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lookup $lookup): void {
            if (filled($lookup->code)) {
                return;
            }

            $lookup->code = static::uniqueCode(
                (string) $lookup->type,
                (string) ($lookup->value ?: $lookup->label_ar),
            );
        });

        static::saved(function (Lookup $lookup): void {
            if (! data_get($lookup->meta, 'is_default')) {
                return;
            }

            static::query()
                ->where('type', $lookup->type)
                ->whereKeyNot($lookup->id)
                ->get()
                ->each(function (Lookup $other): void {
                    if (! data_get($other->meta, 'is_default')) {
                        return;
                    }

                    $meta = $other->meta ?? [];
                    $meta['is_default'] = false;
                    $other->forceFill(['meta' => $meta])->saveQuietly();
                });
        });
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

    public static function uniqueCode(string $type, string $source): string
    {
        $base = trim($source);
        $base = preg_replace('/\s+/u', '-', $base) ?: 'option';
        $base = mb_substr($base, 0, 50);
        $code = $base;
        $suffix = 2;

        while (static::query()->where('type', $type)->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    public static function defaultId(string $type): ?int
    {
        $rows = static::ofType($type)->get();
        $marked = $rows->first(fn (self $row): bool => (bool) data_get($row->meta, 'is_default'));

        return ($marked ?? $rows->first())?->id;
    }

    /** @return Collection<int, Lookup> */
    public static function forType(string $type): Collection
    {
        return static::ofType($type)->get();
    }
}
