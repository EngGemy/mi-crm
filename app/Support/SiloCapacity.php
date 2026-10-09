<?php

namespace App\Support;

use App\Models\Lookup;

class SiloCapacity
{
    /** @var array<int, int|null> */
    private static array $lookupIds = [];

    /**
     * حتى 42,000 طائر = 11 طن، فوقها حتى 52,000 = 14 طن،
     * من 52,000 حتى 62,000 = 17 طن، وفوق 62,000 = 25 طن.
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

    public static function allowsSiloCountChoice(int $birds): bool
    {
        return $birds > 62_000;
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
