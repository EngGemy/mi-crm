<?php

namespace App\Enums;

enum PoultryProjectType: string
{
    case Broiler = 'broiler';
    case Layer = 'layer';
    case LayerRearing = 'layer_rearing';

    public function labelAr(): string
    {
        return match ($this) {
            self::Broiler => 'تسمين (بطارية)',
            self::Layer => 'إنتاج بيض (بياض)',
            self::LayerRearing => 'تربية بياض (مستقبلي)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->labelAr()])
            ->all();
    }
}
