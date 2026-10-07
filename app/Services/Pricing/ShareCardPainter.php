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

        $this->gradient($image, $width, $height);
        $red = $this->color($image, 'C00000');
        $gold = $this->color($image, 'E4C98A');
        $cream = $this->color($image, 'F6F1E8');
        $muted = $this->color($image, 'B7A89A');
        $ink = $this->color($image, '1A120F');
        $chip = $this->color($image, '2A211C');
        $white = $this->color($image, 'FFFFFF');

        imagefilledrectangle($image, 0, 0, 390, $height, $this->color($image, '100E0C'));
        imagefilledrectangle($image, 390, 0, 394, $height, $red);
        imagefilledrectangle($image, 0, 0, $width, 8, $red);

        $company = 'إم آي للصناعات المعدنية';
        try {
            $fromSettings = settings('company.name_ar');
            if (is_string($fromSettings) && trim($fromSettings) !== '') {
                $company = trim($fromSettings);
            }
        } catch (\Throwable) {
        }

        $this->center($image, 'MI', 64, 168, 195, $gold, $bold);
        $this->center($image, 'METAL INDUSTRIES', 14, 214, 195, $cream, $regular);
        imagefilledrectangle($image, 145, 236, 245, 238, $gold);
        $this->centerFit($image, $company, 16, 278, 195, 330, $muted, $regular);

        $type = (string) ($quotation->project_type_label ?: 'عرض سعر');
        $this->center($image, $type, 22, 360, 195, $cream, $bold);
        $this->center($image, (string) ($quotation->quote_number ?: ''), 16, 500, 195, $gold, $bold);
        $this->center($image, (string) ($quotation->created_at?->format('Y / m / d') ?: ''), 14, 536, 195, $muted, $regular);

        $this->right($image, 'عرض سعر خاص', 16, 86, 1144, $gold, $bold);

        $this->right($image, 'مقدّم إلى', 16, 148, 1144, $muted, $regular);
        $this->rightFit($image, (string) ($quotation->client_name ?: 'عميلنا الكريم'), 42, 210, 1144, 700, $cream, $bold);

        imagefilledrectangle($image, 1064, 232, 1144, 235, $red);

        $metrics = [
            ['الطول', $this->meters((float) $quotation->length), 'متر'],
            ['العرض', $this->meters((float) $quotation->width), 'متر'],
            ['الارتفاع', $this->meters((float) $quotation->height), 'متر'],
            ['السعة', number_format((int) $quotation->bird_count), 'طائر'],
        ];
        $boxW = 166;
        $gap = 18;
        $boxY = 268;
        $rightEdge = 1128;
        foreach ($metrics as $index => [$label, $value, $unit]) {
            $x = $rightEdge - (($index + 1) * $boxW) - ($index * $gap);
            imagefilledrectangle($image, $x, $boxY, $x + $boxW, $boxY + 118, $chip);
            $this->centerAt($image, $label, 14, $boxY + 32, $x, $boxW, $muted, $regular);
            $this->centerAt($image, $value, 28, $boxY + 74, $x, $boxW, $white, $bold, false);
            $this->centerAt($image, $unit, 13, $boxY + 102, $x, $boxW, $gold, $regular);
        }

        imagefilledrectangle($image, 410, 424, 1128, 574, $cream);
        $this->right($image, 'الإجمالي التقريبي', 16, 472, 1096, $this->color($image, '8A8178'), $regular);
        $amount = number_format($this->displayTotal($quotation), 0);
        $this->right($image, $amount, 40, 540, 1096, $red, $bold, false);
        $amountWidth = $this->width($amount, 40, $bold);
        $this->right($image, 'جنيه', 20, 534, 1096 - $amountWidth - 16, $red, $bold);

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
    private function gradient($image, int $width, int $height): void
    {
        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $color = imagecolorallocate(
                $image,
                (int) (28 + (14 - 28) * $t),
                (int) (20 + (12 - 20) * $t),
                (int) (18 + (11 - 18) * $t)
            );
            imageline($image, 0, $y, $width, $y, $color);
        }
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
