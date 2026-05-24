<?php

namespace App\Models\Concerns;

trait NormalizesMoneyAttributes
{
    /**
     * @return ($allowNull is true ? ?string : string)
     */
    protected static function normalizeMoneyForStorage(mixed $value, bool $allowNullWhenEmpty = false): ?string
    {
        if ($value === null || $value === '') {
            return $allowNullWhenEmpty ? null : '0.00';
        }

        if (is_string($value)) {
            $value = str_replace(',', '', $value);
            $value = preg_replace('/[^\d.\-]/', '', $value);
            if ($value === '' || $value === '-' || $value === '.') {
                return $allowNullWhenEmpty ? null : '0.00';
            }
        }

        $float = (float) $value;

        if ($float < 0) {
            throw new \InvalidArgumentException('Money value cannot be negative');
        }

        if ($float > 999999999999.99) {
            throw new \InvalidArgumentException('Money value out of range');
        }

        return number_format($float, 2, '.', '');
    }

    protected static function normalizeMoneyFromStorage(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
