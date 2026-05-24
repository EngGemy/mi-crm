<?php

namespace App\Support;

class PoultrySectionLabels
{
    /** @return array<string, string> */
    public static function labelsAr(): array
    {
        return [
            'civil' => 'الإنشاءات',
            'cages' => 'البطاريات',
            'ventilation' => 'التهوية والشفاطات',
            'cooling' => 'التبريد',
            'technical' => 'الدفايات والمشتملات الفنية',
            'electrical' => 'الكهرباء والتحكم',
        ];
    }

    public static function labelAr(string $section): string
    {
        return self::labelsAr()[$section] ?? $section;
    }

    /**
     * @return array<string, string>
     */
    public static function displayGroupsAr(): array
    {
        return [
            'civil' => 'الإنشاءات',
            'cages' => 'بطاريات العنبر',
            'ventilation' => 'المشتملات',
            'cooling' => 'المشتملات',
            'technical' => 'المشتملات',
            'electrical' => 'المشتملات',
        ];
    }

    public static function groupLabel(string $section): string
    {
        return self::displayGroupsAr()[$section] ?? $section;
    }
}
