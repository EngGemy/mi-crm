<?php

namespace App\Support;

/**
 * يشكّل العربية لرسم GD: أشكال الحروف المتصلة ثم قلب الاتجاه البصري.
 * كل نداء يكون عربيًا فقط، والأرقام تُرسم وحدها من اليسار لليمين.
 */
class ArabicGdText
{
    /** @var array<string, array{0: string, 1: string, 2: string, 3: string}> isolated, final, initial, medial */
    private const FORMS = [
        'ا' => ['ا', 'ﺎ', 'ا', 'ﺎ'],
        'أ' => ['أ', 'ﺄ', 'أ', 'ﺄ'],
        'إ' => ['إ', 'ﺈ', 'إ', 'ﺈ'],
        'آ' => ['آ', 'ﺂ', 'آ', 'ﺂ'],
        'ب' => ['ب', 'ﺐ', 'ﺑ', 'ﺒ'],
        'ت' => ['ت', 'ﺖ', 'ﺗ', 'ﺘ'],
        'ث' => ['ث', 'ﺚ', 'ﺛ', 'ﺜ'],
        'ج' => ['ج', 'ﺞ', 'ﺟ', 'ﺠ'],
        'ح' => ['ح', 'ﺢ', 'ﺣ', 'ﺤ'],
        'خ' => ['خ', 'ﺦ', 'ﺧ', 'ﺨ'],
        'د' => ['د', 'ﺪ', 'د', 'ﺪ'],
        'ذ' => ['ذ', 'ﺬ', 'ذ', 'ﺬ'],
        'ر' => ['ر', 'ﺮ', 'ر', 'ﺮ'],
        'ز' => ['ز', 'ﺰ', 'ز', 'ﺰ'],
        'س' => ['س', 'ﺲ', 'ﺳ', 'ﺴ'],
        'ش' => ['ش', 'ﺶ', 'ﺷ', 'ﺸ'],
        'ص' => ['ص', 'ﺺ', 'ﺻ', 'ﺼ'],
        'ض' => ['ض', 'ﺾ', 'ﺿ', 'ﻀ'],
        'ط' => ['ط', 'ﻂ', 'ﻃ', 'ﻄ'],
        'ظ' => ['ظ', 'ﻆ', 'ﻇ', 'ﻈ'],
        'ع' => ['ع', 'ﻊ', 'ﻋ', 'ﻌ'],
        'غ' => ['غ', 'ﻎ', 'ﻏ', 'ﻐ'],
        'ف' => ['ف', 'ﻒ', 'ﻓ', 'ﻔ'],
        'ق' => ['ق', 'ﻖ', 'ﻗ', 'ﻘ'],
        'ك' => ['ك', 'ﻚ', 'ﻛ', 'ﻜ'],
        'ل' => ['ل', 'ﻞ', 'ﻟ', 'ﻠ'],
        'م' => ['م', 'ﻢ', 'ﻣ', 'ﻤ'],
        'ن' => ['ن', 'ﻦ', 'ﻧ', 'ﻨ'],
        'ه' => ['ه', 'ﻪ', 'ﻫ', 'ﻬ'],
        'و' => ['و', 'ﻮ', 'و', 'ﻮ'],
        'ؤ' => ['ؤ', 'ﺆ', 'ؤ', 'ﺆ'],
        'ي' => ['ي', 'ﻲ', 'ﻳ', 'ﻴ'],
        'ى' => ['ى', 'ﻰ', 'ى', 'ﻰ'],
        'ئ' => ['ئ', 'ﺊ', 'ﺋ', 'ﺌ'],
        'ة' => ['ة', 'ﺔ', 'ة', 'ﺔ'],
        'ء' => ['ء', 'ء', 'ء', 'ء'],
    ];

    /** @var array<string, array{0: string, 1: string}> isolated, final */
    private const LAM_ALEF = [
        'ا' => ['ﻻ', 'ﻼ'],
        'أ' => ['ﻷ', 'ﻸ'],
        'إ' => ['ﻹ', 'ﻺ'],
        'آ' => ['ﻵ', 'ﻶ'],
    ];

    private const DUAL = 'بتثجحخسشصضطظعغفقكلمنهيئ';

    private const RIGHT = 'اأإآدذرزوؤةى';

    public function visual(string $text): string
    {
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $shaped = [];
        $count = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $current = $chars[$i];
            $next = $chars[$i + 1] ?? '';

            if ($current === 'ل' && isset(self::LAM_ALEF[$next])) {
                $shaped[] = self::LAM_ALEF[$next][$this->connectsForward($chars[$i - 1] ?? '') ? 1 : 0];
                $i++;

                continue;
            }

            if (! isset(self::FORMS[$current])) {
                $shaped[] = $current;

                continue;
            }

            $prev = $this->connectsForward($chars[$i - 1] ?? '');
            $joinsNext = $this->isDual($current) && $this->joinsPrevious($next);
            $form = match (true) {
                $prev && $joinsNext => 3,
                $prev => 1,
                $joinsNext => 2,
                default => 0,
            };
            $shaped[] = self::FORMS[$current][$form];
        }

        return implode('', array_reverse($shaped));
    }

    private function connectsForward(string $char): bool
    {
        return $char !== '' && mb_strpos(self::DUAL, $char) !== false;
    }

    private function joinsPrevious(string $char): bool
    {
        return $char !== '' && (mb_strpos(self::DUAL.self::RIGHT, $char) !== false || $char === 'ل');
    }

    private function isDual(string $char): bool
    {
        return mb_strpos(self::DUAL, $char) !== false;
    }
}
