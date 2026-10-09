<?php

namespace App\Enums;

enum PoultryProjectType: string
{
    case Broiler = 'broiler';
    case BroilerAutoExit = 'broiler_auto_exit';
    case Layer = 'layer';
    case LayerAutoCollect = 'layer_auto_collect';
    case LayerRearing = 'layer_rearing';

    public function labelAr(): string
    {
        return match ($this) {
            self::Broiler => 'تسمين',
            self::BroilerAutoExit => 'تسمين تخريج آلي',
            self::Layer => 'إنتاج بياض',
            self::LayerAutoCollect => 'إنتاج بياض جمع آلي',
            self::LayerRearing => 'تربية بياض',
        };
    }

    public function isBroiler(): bool
    {
        return $this === self::Broiler || $this === self::BroilerAutoExit;
    }

    public function isLayer(): bool
    {
        return $this->pricesAs() === self::Layer;
    }

    /**
     * سعر الطائر بالدولار. 4 أدوار فأكثر بسعر الأربع أدوار، وأقل من ذلك بسعر الثلاث.
     */
    public function birdPriceUsd(int $tiers): ?float
    {
        $fourTiers = $tiers >= 4;

        return match ($this) {
            self::Broiler => $fourTiers ? 2.8 : 2.9,
            self::BroilerAutoExit => $fourTiers ? 3.5 : 3.6,
            self::Layer => $fourTiers ? 2.8 : 2.9,
            self::LayerAutoCollect => $fourTiers ? 3.1 : 3.2,
            self::LayerRearing => null,
        };
    }

    /** التخريج الآلي يحسب كتسمين، وجمع البيض وتربية البياض يحسبان كإنتاج بياض. */
    public function pricesAs(): self
    {
        return match ($this) {
            self::BroilerAutoExit => self::Broiler,
            self::LayerAutoCollect, self::LayerRearing => self::Layer,
            default => $this,
        };
    }

    public static function options(): array
    {
        return collect([
            self::Broiler,
            self::BroilerAutoExit,
            self::Layer,
            self::LayerAutoCollect,
            self::LayerRearing,
        ])->mapWithKeys(fn (self $t) => [$t->value => $t->labelAr()])->all();
    }
}
