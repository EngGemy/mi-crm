<?php

namespace App\Support;

use App\Models\Lookup;

class SiloCapacity
{
    /** @var array<int, int|null> */
    private static array $lookupIds = [];

    /**
     * حتى 42,000 طائر = 11 طن، فوقها حتى 52,000 = 14 طن،
     * من 52,000 حتى 62,000 = 17 طن. فوق 62,000 الاختيار مفتوح.
     */
    public static function tonsForBirdCount(int $birds): ?int
    {
        if ($birds <= 0) {
            return null;
        }

        if ($birds <= 42_000) {
            return 11;
        }

        if ($birds < 52_000) {
            return 14;
        }

        if ($birds <= 62_000) {
            return 17;
        }

        return 25;
    }

    /** @return list<int> */
    public static function openChoiceTons(): array
    {
        return [14, 17, 25];
    }

    public static function allowsSiloCountChoice(int $birds): bool
    {
        return $birds > 62_000;
    }

    public static function labelForBirds(int $birds, ?string $selected = null): string
    {
        if (self::allowsSiloCountChoice($birds)) {
            $chosen = self::chosenTons($selected);

            return ($chosen ?? 25).' طن';
        }

        $tons = self::tonsForBirdCount($birds);
        if ($tons !== null) {
            return $tons.' طن';
        }

        return is_string($selected) && $selected !== '' ? $selected : '25 طن';
    }

    public static function chosenTons(?string $selected): ?int
    {
        if (! is_string($selected) || ! preg_match('/(\d+)/', $selected, $match)) {
            return null;
        }

        $tons = (int) $match[1];

        return in_array($tons, self::openChoiceTons(), true) ? $tons : null;
    }

    /** @return array<int, string> */
    public static function openChoiceOptions(): array
    {
        return Lookup::query()
            ->where('type', Lookup::TYPE_SILO_CAPACITY)
            ->whereIn('code', array_map('strval', self::openChoiceTons()))
            ->orderBy('code')
            ->pluck('label_ar', 'id')
            ->all();
    }

    public static function lookupIdForTons(int $tons): ?int
    {
        if (! array_key_exists($tons, self::$lookupIds)) {
            $id = Lookup::query()
                ->where('type', Lookup::TYPE_SILO_CAPACITY)
                ->where('code', (string) $tons)
                ->value('id');

            self::$lookupIds[$tons] = $id !== null ? (int) $id : null;
        }

        return self::$lookupIds[$tons];
    }
}
