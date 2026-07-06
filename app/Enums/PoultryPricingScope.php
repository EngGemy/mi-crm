<?php

namespace App\Enums;

enum PoultryPricingScope: string
{
    case FullProject = 'full_project';
    case BatteriesOnly = 'batteries_only';
    case BatteriesAndAccessories = 'batteries_and_accessories';
    case AccessoriesOnly = 'accessories_only';
    case ConstructionOnly = 'construction_only';
    case Custom = 'custom';

    public function labelAr(): string
    {
        return match ($this) {
            self::FullProject => 'مشروع كامل',
            self::BatteriesOnly => 'بطاريات فقط',
            self::BatteriesAndAccessories => 'بطاريات ومشتملات',
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

    public static function fromQuotationTypeCode(?string $code): ?self
    {
        return match ($code) {
            'CONSTRUCTION_ONLY' => self::ConstructionOnly,
            'CAGES_ONLY' => self::BatteriesOnly,
            'CAGES_AND_ACCESSORIES' => self::BatteriesAndAccessories,
            'FULL_PROJECT' => self::FullProject,
            'ACCESSORIES_ONLY' => self::AccessoriesOnly,
            default => null,
        };
    }

    /** @return list<string> */
    public function includedSections(): array
    {
        return match ($this) {
            self::FullProject => ['civil', 'cages', 'ventilation', 'cooling', 'technical', 'electrical'],
            self::BatteriesOnly => ['cages'],
            self::BatteriesAndAccessories => ['cages', 'ventilation', 'cooling', 'technical', 'electrical'],
            self::AccessoriesOnly => ['ventilation', 'cooling', 'technical', 'electrical'],
            self::ConstructionOnly => ['civil'],
            self::Custom => ['civil', 'cages', 'ventilation', 'cooling', 'technical', 'electrical'],
        };
    }
}
