<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use App\Support\ArabicGdText;
use RuntimeException;

/**
 * كارت مشاركة 1200×630 جاهز لمعاينة واتساب.
 */
class ShareCardPainter
{
    public function __construct(private ArabicGdText $arabic = new ArabicGdText) {}

    public function png(PoultryQuotation $quotation): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('امتداد GD غير متاح لتوليد صورة الكارت.');
        }

        $regular = public_path('fonts/Cairo-Regular.ttf');
        $bold = public_path('fonts/Cairo-Bold.ttf');
        if (! is_file($regular) || ! is_file($bold)) {
            throw new RuntimeException('خط Cairo غير موجود في public/fonts.');
        }

        $width = 1200;
        $height = 630;
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);

        $red = $this->color($image, 'C4161C');
        $paper = $this->color($image, 'F6F1E8');
        $ink = $this->color($image, '1A1614');
        $muted = $this->color($image, '8C8178');
        $line = $this->color($image, 'E4D8C8');
        $card = $this->color($image, 'FFFCF8');
        $white = $this->color($image, 'FFFFFF');
        $soft = $this->color($image, 'C9B8A4');

        imagefilledrectangle($image, 0, 0, $width, $height, $paper);
        imagefilledrectangle($image, 0, 0, $width, 12, $red);

        $company = 'إم آي للصناعات المعدنية';
        try {
            $fromSettings = settings('company.name_ar');
            if (is_string($fromSettings) && trim($fromSettings) !== '') {
                $company = trim($fromSettings);
            }
        } catch (\Throwable) {
        }

        $pad = 52;
        $right = $width - $pad;
        $logoW = 198;
        $logoH = $this->pasteLogo($image, $right - $logoW, 32, $logoW);
        $lockupRight = $right - $logoW - 22;
        $this->rightFit($image, $company, 22, 86, $lockupRight, 460, $ink, $bold);
        $this->right($image, 'أقفاص الدواجن الأوتوماتيك', 15, 122, $lockupRight, $muted, $regular);
        imagefilledrectangle($image, $lockupRight - 118, 142, $lockupRight, 145, $red);

        $this->left($image, 'عرض سعر خاص', 16, 78, $pad, $red, $bold);
        $quote = (string) ($quotation->quote_number ?: '');
        if ($quote !== '') {
            $this->left($image, $quote, 18, 114, $pad, $ink, $bold, false);
        }
        $date = (string) ($quotation->created_at?->format('Y / m / d') ?: '');
        if ($date !== '') {
            $this->left($image, $date, 14, 146, $pad, $muted, $regular, false);
        }

        imagefilledrectangle($image, $pad, 186, $right, 188, $line);

        $this->right($image, 'مقدّم إلى', 16, 232, $right, $muted, $regular);
        $type = (string) ($quotation->project_type_label ?: '');
        if ($type !== '') {
            $labelWidth = $this->width($this->arabic->visual('مقدّم إلى'), 16, $regular);
            $this->right($image, $type, 16, 232, $right - $labelWidth - 18, $red, $bold);
        }
        $this->rightFit($image, (string) ($quotation->client_name ?: 'عميلنا الكريم'), 46, 296, $right, 1096, $ink, $bold);
        imagefilledrectangle($image, $right - 132, 314, $right, 318, $red);

        $metrics = [
            ['الطول', $this->meters((float) $quotation->length), 'متر'],
            ['العرض', $this->meters((float) $quotation->width), 'متر'],
            ['الارتفاع', $this->meters((float) $quotation->height), 'متر'],
            ['السعة', number_format((int) $quotation->bird_count), 'طائر'],
        ];
        $boxW = 260;
        $gap = 18;
        $boxY = 348;
        foreach ($metrics as $index => [$label, $value, $unit]) {
            $x = $right - (($index + 1) * $boxW) - ($index * $gap);
            $this->roundRect($image, $x, $boxY, $boxW, 112, 14, $line);
            $this->roundRect($image, $x + 1, $boxY + 1, $boxW - 2, 110, 13, $card);
            imagefilledrectangle($image, $x + 18, $boxY, $x + $boxW - 18, $boxY + 3, $red);
            $this->centerAt($image, $label, 14, $boxY + 36, $x, $boxW, $muted, $regular);
            $this->centerAt($image, $value, 30, $boxY + 76, $x, $boxW, $ink, $bold, false);
            $this->centerAt($image, $unit, 13, $boxY + 100, $x, $boxW, $red, $regular);
        }

        $this->roundRect($image, $pad, 484, $right - $pad, 112, 16, $ink);
        $this->right($image, 'الإجمالي التقريبي', 16, 528, $right - 28, $soft, $regular);
        $amount = number_format($this->displayTotal($quotation), 0);
        $this->right($image, $amount, 36, 572, $right - 28, $white, $bold, false);
        $amountWidth = $this->width($amount, 36, $bold);
        $this->right($image, 'جنيه', 18, 572, $right - 28 - $amountWidth - 14, $white, $bold);
        if ($type !== '') {
            $this->left($image, $type, 16, 548, $pad + 28, $white, $bold);
        }

        ob_start();
        imagepng($image, null, 6);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('تعذر حفظ صورة الكارت.');
        }

        return $binary;
    }

    private function displayTotal(PoultryQuotation $quotation): float
    {
        $total = (float) $quotation->total;
        if ($total > 0) {
            return $total;
        }

        $financial = $quotation->pricing_snapshot['financial'] ?? [];

        return (float) ($financial['total'] ?? $financial['grand_total'] ?? $quotation->subtotal ?? 0);
    }

    private function meters(float $value): string
    {
        if (abs($value - round($value)) < 0.05) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /** @param  \GdImage  $image */
    private function pasteLogo($image, int $x, int $y, int $targetW): int
    {
        $path = public_path('images/brand/mi-logo.png');
        if (! is_file($path)) {
            return 0;
        }

        $src = @imagecreatefrompng($path);
        if ($src === false) {
            return 0;
        }

        imagealphablending($src, true);
        imagesavealpha($src, true);
        $sourceW = imagesx($src);
        $sourceH = imagesy($src);
        $targetH = (int) round($targetW * ($sourceH / max(1, $sourceW)));
        imagealphablending($image, true);
        imagecopyresampled($image, $src, $x, $y, 0, 0, $targetW, $targetH, $sourceW, $sourceH);
        imagedestroy($src);

        return $targetH;
    }

    /** @param  \GdImage  $image */
    private function roundRect($image, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        imagefilledrectangle($image, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($image, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        imagefilledellipse($image, $x + $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
    }

    /** @param  \GdImage  $image */
    private function left($image, string $text, int $size, int $baseline, int $x, int $color, string $font, bool $arabic = true): void
    {
        $visual = $arabic ? $this->arabic->visual($text) : $text;
        if ($this->hasArabic($text)) {
            $visual = $this->arabic->visual($text);
        }
        imagettftext($image, $size, 0, $x, $baseline, $color, $font, $visual);
    }

    /** @param  \GdImage  $image */
    private function color($image, string $hex): int
    {
        return imagecolorallocate($image, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    /** @param  \GdImage  $image */
    private function right($image, string $text, int $size, int $baseline, int $right, int $color, string $font, bool $arabic = true): void
    {
        $visual = $arabic ? $this->arabic->visual($text) : $text;
        $width = $this->width($visual, $size, $font);
        imagettftext($image, $size, 0, $right - $width, $baseline, $color, $font, $visual);
    }

    /** @param  \GdImage  $image */
    private function rightFit($image, string $text, int $size, int $baseline, int $right, int $maxWidth, int $color, string $font): void
    {
        $visual = $this->arabic->visual($text);
        while ($size > 22 && $this->width($visual, $size, $font) > $maxWidth) {
            $size -= 2;
        }
        $width = $this->width($visual, $size, $font);
        imagettftext($image, $size, 0, $right - $width, $baseline, $color, $font, $visual);
    }

    /** @param  \GdImage  $image */
    private function center($image, string $text, int $size, int $baseline, int $centerX, int $color, string $font, bool $arabic = false): void
    {
        $visual = $arabic ? $this->arabic->visual($text) : $text;
        if ($this->hasArabic($text)) {
            $visual = $this->arabic->visual($text);
        }
        $width = $this->width($visual, $size, $font);
        imagettftext($image, $size, 0, (int) ($centerX - ($width / 2)), $baseline, $color, $font, $visual);
    }

    /** @param  \GdImage  $image */
    private function centerFit($image, string $text, int $size, int $baseline, int $centerX, int $maxWidth, int $color, string $font): void
    {
        $visual = $this->hasArabic($text) ? $this->arabic->visual($text) : $text;
        while ($size > 12 && $this->width($visual, $size, $font) > $maxWidth) {
            $size--;
        }
        $width = $this->width($visual, $size, $font);
        imagettftext($image, $size, 0, (int) ($centerX - ($width / 2)), $baseline, $color, $font, $visual);
    }

    /** @param  \GdImage  $image */
    private function centerAt($image, string $text, int $size, int $baseline, int $boxX, int $boxW, int $color, string $font, bool $arabic = true): void
    {
        $visual = ($arabic && $this->hasArabic($text)) ? $this->arabic->visual($text) : $text;
        $width = $this->width($visual, $size, $font);
        imagettftext($image, $size, 0, (int) ($boxX + (($boxW - $width) / 2)), $baseline, $color, $font, $visual);
    }

    private function hasArabic(string $text): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $text);
    }

    private function width(string $visual, int $size, string $font): int
    {
        $box = imagettfbbox($size, 0, $font, $visual) ?: [0, 0, 0, 0, 0, 0, 0, 0];

        return abs($box[2] - $box[0]);
    }
}
