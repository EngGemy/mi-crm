<?php

namespace App\Enums;

enum PoultryPricingScope: string
{
    case FullProject = 'full_project';
    case BatteriesOnly = 'batteries_only';
    case AccessoriesOnly = 'accessories_only';
    case ConstructionOnly = 'construction_only';
    case Custom = 'custom';

    public function labelAr(): string
    {
        return match ($this) {
            self::FullProject => 'مشروع كامل',
            self::BatteriesOnly => 'بطاريات فقط',
            self::AccessoriesOnly => 'مشتملات/أنظمة فقط',
            self::ConstructionOnly => 'إنشاءات فقط',
            self::Custom => 'بنود مخصصة',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->labelAr()])
            ->all();
    }

    /** @return list<string> */
    public function includedSections(): array
    {
        return match ($this) {
            self::FullProject => ['civil', 'cages', 'ventilation', 'cooling', 'technical', 'electrical'],
            self::BatteriesOnly => ['cages'],
            self::AccessoriesOnly => ['ventilation', 'cooling', 'technical', 'electrical'],
            self::ConstructionOnly => ['civil'],
            self::Custom => ['civil', 'cages', 'ventilation', 'cooling', 'technical', 'electrical'],
        };
    }
}
