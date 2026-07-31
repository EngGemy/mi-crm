<?php

namespace App\Support;

/**
 * Minimal Arabic letter reshaper for GD imagettftext (presentation forms).
 */
class ArabicText
{
    /** @var array<string, array{0:?string,1:?string,2:?string,3:?string,4:bool}> */
    private const LETTERS = [
        'ء' => ['ﺀ', 'ﺀ', null, null, false],
        'آ' => ['ﺁ', 'ﺂ', null, null, false],
        'أ' => ['ﺃ', 'ﺄ', null, null, false],
        'ؤ' => ['ﺅ', 'ﺆ', null, null, false],
        'إ' => ['ﺇ', 'ﺈ', null, null, false],
        'ئ' => ['ﺉ', 'ﺊ', 'ﺋ', 'ﺌ', true],
        'ا' => ['ﺍ', 'ﺎ', null, null, false],
        'ب' => ['ﺏ', 'ﺐ', 'ﺑ', 'ﺒ', true],
        'ة' => ['ﺓ', 'ﺔ', null, null, false],
        'ت' => ['ﺕ', 'ﺖ', 'ﺗ', 'ﺘ', true],
        'ث' => ['ﺙ', 'ﺚ', 'ﺛ', 'ﺜ', true],
        'ج' => ['ﺝ', 'ﺞ', 'ﺟ', 'ﺠ', true],
        'ح' => ['ﺡ', 'ﺢ', 'ﺣ', 'ﺤ', true],
        'خ' => ['ﺥ', 'ﺦ', 'ﺧ', 'ﺨ', true],
        'د' => ['ﺩ', 'ﺪ', null, null, false],
        'ذ' => ['ﺫ', 'ﺬ', null, null, false],
        'ر' => ['ﺭ', 'ﺮ', null, null, false],
        'ز' => ['ﺯ', 'ﺰ', null, null, false],
        'س' => ['ﺱ', 'ﺲ', 'ﺳ', 'ﺴ', true],
        'ش' => ['ﺵ', 'ﺶ', 'ﺷ', 'ﺸ', true],
        'ص' => ['ﺹ', 'ﺺ', 'ﺻ', 'ﺼ', true],
        'ض' => ['ﺽ', 'ﺾ', 'ﺿ', 'ﻀ', true],
        'ط' => ['ﻁ', 'ﻂ', 'ﻃ', 'ﻄ', true],
        'ظ' => ['ﻅ', 'ﻆ', 'ﻇ', 'ﻈ', true],
        'ع' => ['ﻉ', 'ﻊ', 'ﻋ', 'ﻌ', true],
        'غ' => ['ﻍ', 'ﻎ', 'ﻏ', 'ﻐ', true],
        'ف' => ['ﻑ', 'ﻒ', 'ﻓ', 'ﻔ', true],
        'ق' => ['ﻕ', 'ﻖ', 'ﻗ', 'ﻘ', true],
        'ك' => ['ﻙ', 'ﻚ', 'ﻛ', 'ﻜ', true],
        'ل' => ['ﻝ', 'ﻞ', 'ﻟ', 'ﻠ', true],
        'م' => ['ﻡ', 'ﻢ', 'ﻣ', 'ﻤ', true],
        'ن' => ['ﻥ', 'ﻦ', 'ﻧ', 'ﻨ', true],
        'ه' => ['ﻩ', 'ﻪ', 'ﻫ', 'ﻬ', true],
        'و' => ['ﻭ', 'ﻮ', null, null, false],
        'ى' => ['ﻯ', 'ﻰ', null, null, false],
        'ي' => ['ﻱ', 'ﻲ', 'ﻳ', 'ﻴ', true],
        'لا' => ['ﻻ', 'ﻼ', null, null, false],
    ];

    public static function forGd(string $text): string
    {
        if ($text === '' || ! preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        $n = count($chars);

        for ($i = 0; $i < $n; $i++) {
            $ch = $chars[$i];

            if ($ch === 'ل' && isset($chars[$i + 1]) && in_array($chars[$i + 1], ['ا', 'أ', 'إ', 'آ'], true)) {
                $form = self::LETTERS['لا'];
                $prevConnects = self::connectsAfter($chars, $i - 1);
                $out[] = $prevConnects ? ($form[1] ?? $form[0]) : $form[0];
                $i++;

                continue;
            }

            if (! isset(self::LETTERS[$ch])) {
                $out[] = $ch;

                continue;
            }

            $form = self::LETTERS[$ch];
            $before = self::connectsAfter($chars, $i - 1);
            $after = $form[4] && self::connectsBefore($chars, $i + 1);

            if ($before && $after && $form[3]) {
                $out[] = $form[3];
            } elseif ($before && $form[1]) {
                $out[] = $form[1];
            } elseif ($after && $form[2]) {
                $out[] = $form[2];
            } else {
                $out[] = $form[0];
            }
        }

        return implode('', array_reverse($out));
    }

    private static function connectsAfter(array $chars, int $i): bool
    {
        if ($i < 0 || ! isset($chars[$i])) {
            return false;
        }
        $ch = $chars[$i];
        if ($ch === 'ل' && isset($chars[$i + 1]) && in_array($chars[$i + 1], ['ا', 'أ', 'إ', 'آ'], true)) {
            return false;
        }

        return isset(self::LETTERS[$ch]) && (self::LETTERS[$ch][4] === true);
    }

    private static function connectsBefore(array $chars, int $i): bool
    {
        if (! isset($chars[$i])) {
            return false;
        }

        return isset(self::LETTERS[$chars[$i]]);
    }
}
