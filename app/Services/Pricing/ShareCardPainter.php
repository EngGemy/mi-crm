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
        imagefilledrectangle($image, 0, 0, $width, 8, $red);

        $profile = $this->profile();
        $company = $profile['name'];

        $pad = 48;
        $right = $width - $pad;
        $logoW = 148;
        $this->pasteLogo($image, $right - $logoW, 18, $logoW);
        $lockupRight = $right - $logoW - 16;
        $this->rightFit($image, $company, 20, 46, $lockupRight, 520, $ink, $bold);
        $this->right($image, $profile['tagline'], 13, 72, $lockupRight, $muted, $regular);
        if ($profile['name_en'] !== '') {
            $this->right($image, $profile['name_en'], 12, 96, $lockupRight, $muted, $regular, false);
        }
        imagefilledrectangle($image, $lockupRight - 96, 108, $lockupRight, 111, $red);

        $this->left($image, 'عرض سعر خاص', 15, 44, $pad, $red, $bold);
        $quote = (string) ($quotation->quote_number ?: '');
        if ($quote !== '') {
            $this->left($image, $quote, 18, 72, $pad, $ink, $bold, false);
        }
        $date = (string) ($quotation->created_at?->format('Y / m / d') ?: '');
        if ($date !== '') {
            $this->left($image, $date, 13, 96, $pad, $muted, $regular, false);
        }

        imagefilledrectangle($image, $pad, 124, $right, 126, $line);

        $this->right($image, 'مقدّم إلى', 15, 160, $right, $muted, $regular);
        $type = (string) ($quotation->project_type_label ?: '');
        if ($type !== '') {
            $labelWidth = $this->width($this->arabic->visual('مقدّم إلى'), 15, $regular);
            $this->right($image, $type, 15, 160, $right - $labelWidth - 16, $red, $bold);
        }
        $this->rightFit($image, (string) ($quotation->client_name ?: 'عميلنا الكريم'), 36, 208, $right, 1100, $ink, $bold);
        imagefilledrectangle($image, $right - 120, 222, $right, 226, $red);

        $metrics = [
            ['الطول', $this->meters((float) $quotation->length), 'متر'],
            ['العرض', $this->meters((float) $quotation->width), 'متر'],
            ['الارتفاع', $this->meters((float) $quotation->height), 'متر'],
            ['السعة', number_format((int) $quotation->bird_count), 'طائر'],
        ];
        $gap = 16;
        $boxW = (int) floor((($right - $pad) - ((count($metrics) - 1) * $gap)) / count($metrics));
        $boxY = 246;
        foreach ($metrics as $index => [$label, $value, $unit]) {
            $x = $right - (($index + 1) * $boxW) - ($index * $gap);
            $this->roundRect($image, $x, $boxY, $boxW, 92, 14, $line);
            $this->roundRect($image, $x + 1, $boxY + 1, $boxW - 2, 90, 13, $card);
            imagefilledrectangle($image, $x + 16, $boxY, $x + $boxW - 16, $boxY + 3, $red);
            $this->centerAt($image, $label, 13, $boxY + 30, $x, $boxW, $muted, $regular);
            $this->centerAt($image, $value, 26, $boxY + 62, $x, $boxW, $ink, $bold, false);
            $this->centerAt($image, $unit, 12, $boxY + 82, $x, $boxW, $red, $regular);
        }

        $totalY = 348;
        $this->roundRect($image, $pad, $totalY, $right - $pad, 92, 16, $ink);
        $this->right($image, 'الإجمالي التقريبي', 14, $totalY + 28, $right - 28, $soft, $regular);
        $amount = number_format($this->displayTotal($quotation), 0);
        $this->right($image, $amount, 30, $totalY + 72, $right - 28, $white, $bold, false);
        $amountWidth = $this->width($amount, 30, $bold);
        $this->right($image, 'جنيه', 16, $totalY + 70, $right - 28 - $amountWidth - 12, $white, $bold);
        if ($profile['owner'] !== '') {
            $this->leftFit($image, $profile['owner'], 15, $totalY + 36, $pad + 26, 460, $white, $bold);
            if ($profile['owner_title'] !== '') {
                $this->leftFit($image, $profile['owner_title'], 12, $totalY + 66, $pad + 26, 460, $soft, $regular);
            }
        } elseif ($type !== '') {
            $this->left($image, $type, 16, $totalY + 52, $pad + 26, $white, $bold);
        }

        $this->drawIdentity($image, 470, (int) ($width / 2), $profile, $muted, $ink, $regular, $bold);

        $footerY = 488;
        $this->drawFooter($image, $pad, $footerY, $right - $pad, 124, $this->footerColumns($profile), $ink, $soft, $white, $regular, $bold);
        imagefilledrectangle($image, 0, $height - 8, $width, $height, $red);

        ob_start();
        imagepng($image, null, 6);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('تعذر حفظ صورة الكارت.');
        }

        return $binary;
    }

    /** @return array{name: string, name_en: string, tagline: string, owner: string, owner_title: string, address: string, phones: list<string>, whatsapp: string, email: string, website: string, tax: string, register: string} */
    public function profile(): array
    {
        $phones = $this->setting('contact.phones', []);
        if (is_string($phones)) {
            $decoded = json_decode($phones, true);
            $phones = is_array($decoded) ? $decoded : preg_split('/[,|\n]+/', $phones);
        }
        if (! is_array($phones)) {
            $phones = [];
        }
        $phones = array_values(array_filter(array_map(fn ($phone) => trim((string) $phone), $phones)));

        return [
            'name' => $this->settingString('company.name_ar', 'إم آي للصناعات المعدنية'),
            'name_en' => $this->settingString('company.name_en', 'MI Metal Industries'),
            'tagline' => $this->settingString('company.tagline_ar', 'بطاريات الدواجن الأوتوماتيك'),
            'owner' => $this->settingString('company.owner_name_ar', 'محمد مأمون مصطفى المغربي'),
            'owner_title' => $this->settingString('company.owner_title_ar', 'رئيس مجلس الإدارة'),
            'address' => $this->settingString('contact.address_ar', 'طريق رأس البر القديم - السنانية - دمياط'),
            'phones' => array_map(
                fn (string $phone) => $this->localPhone($phone),
                $phones !== [] ? $phones : ['+201026253004', '+201011004114', '+20572363679'],
            ),
            'whatsapp' => $this->localPhone($this->settingString('contact.whatsapp', '+201026253004')),
            'email' => $this->settingString('contact.email', 'mi.cnc.factory@gmail.com'),
            'website' => $this->settingString('contact.website', 'www.mi-cnc.com'),
            'tax' => $this->settingString('legal.tax_number', '194/577/443'),
            'register' => $this->settingString('legal.commercial_register', '97483'),
        ];
    }

    /** @param  array<string, mixed>  $profile
     * @return list<array{label: string, lines: list<array{text: string, arabic: bool}>}>
     */
    private function footerColumns(array $profile): array
    {
        $columns = [];
        if ($profile['address'] !== '') {
            $columns[] = ['label' => 'المقر', 'lines' => [['text' => $profile['address'], 'arabic' => true]]];
        }

        $phones = [];
        foreach (array_merge([$profile['whatsapp']], $profile['phones']) as $phone) {
            $phone = trim((string) $phone);
            if ($phone !== '' && ! in_array($phone, $phones, true)) {
                $phones[] = $phone;
            }
        }
        if ($phones !== []) {
            $lines = count($phones) > 2
                ? [$phones[0], $phones[1].' · '.$phones[2]]
                : $phones;
            $columns[] = [
                'label' => 'التليفون',
                'lines' => array_map(fn (string $phone) => ['text' => $phone, 'arabic' => false], array_slice($lines, 0, 2)),
            ];
        }

        $mail = array_values(array_filter([$profile['email'], $profile['website']]));
        if ($mail !== []) {
            $columns[] = [
                'label' => 'المراسلة',
                'lines' => array_map(fn (string $line) => ['text' => $line, 'arabic' => false], $mail),
            ];
        }

        return $columns;
    }

    /** @param  \GdImage  $image
     * @param  array<string, mixed>  $profile
     */
    private function drawIdentity($image, int $baseline, int $centerX, array $profile, int $labelColor, int $valueColor, string $regular, string $bold): void
    {
        $pairs = array_values(array_filter([
            $profile['tax'] !== '' ? ['الرقم الضريبي', $profile['tax']] : null,
            $profile['register'] !== '' ? ['السجل التجاري', $profile['register']] : null,
        ]));
        if ($pairs === []) {
            return;
        }

        $gap = 28;
        $inner = 8;
        $size = 13;
        $chunks = [];
        $total = 0;
        foreach ($pairs as [$label, $value]) {
            $visual = $this->arabic->visual($label);
            $labelWidth = $this->width($visual, $size, $regular);
            $valueWidth = $this->width($value, $size, $bold);
            $chunks[] = [$visual, $value, $labelWidth, $valueWidth];
            $total += $labelWidth + $inner + $valueWidth;
        }
        $total += $gap * (count($chunks) - 1);
        $cursor = (int) ($centerX + ($total / 2));
        foreach ($chunks as [$visual, $value, $labelWidth, $valueWidth]) {
            $cursor -= $labelWidth;
            imagettftext($image, $size, 0, $cursor, $baseline, $labelColor, $regular, $visual);
            $cursor -= $inner + $valueWidth;
            imagettftext($image, $size, 0, $cursor, $baseline, $valueColor, $bold, $value);
            $cursor -= $gap;
        }
    }

    /** @param  \GdImage  $image
     * @param  list<array{label: string, lines: list<array{text: string, arabic: bool}>}>  $columns
     */
    private function drawFooter($image, int $x, int $y, int $w, int $h, array $columns, int $ink, int $soft, int $white, string $regular, string $bold): void
    {
        $this->roundRect($image, $x, $y, $w, $h, 16, $ink);
        $count = count($columns);
        if ($count === 0) {
            return;
        }

        $pad = 24;
        $gap = 18;
        $colW = (int) (($w - ($pad * 2) - ($gap * ($count - 1))) / $count);
        $divider = $this->color($image, '3C352F');
        foreach ($columns as $index => $column) {
            $colX = $x + $w - $pad - (($index + 1) * $colW) - ($index * $gap);
            if ($index > 0) {
                imagefilledrectangle($image, $colX + $colW + (int) ($gap / 2), $y + 22, $colX + $colW + (int) ($gap / 2) + 1, $y + $h - 22, $divider);
            }
            $this->centerAt($image, $column['label'], 13, $y + 34, $colX, $colW, $soft, $regular);
            $lineY = count($column['lines']) === 1 ? $y + 82 : $y + 66;
            foreach ($column['lines'] as $line) {
                $font = $line['arabic'] ? $regular : $bold;
                $this->centerFit($image, $line['text'], 15, $lineY, $colX + (int) ($colW / 2), $colW - 12, $white, $font);
                $lineY += 30;
            }
        }
    }

    private function localPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '20') && strlen($digits) > 10) {
            return '0'.substr($digits, 2);
        }

        return trim($phone);
    }

    private function setting(string $key, mixed $default): mixed
    {
        try {
            return settings($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    private function settingString(string $key, string $default, bool $fallbackOnEmpty = true): string
    {
        $value = $this->setting($key, $default);
        if (! is_string($value) && ! is_numeric($value)) {
            return $fallbackOnEmpty ? $default : '';
        }

        $text = trim((string) $value);
        if ($text === '') {
            return $fallbackOnEmpty ? $default : '';
        }

        return $text;
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
    private function leftFit($image, string $text, int $size, int $baseline, int $x, int $maxWidth, int $color, string $font): void
    {
        $visual = $this->hasArabic($text) ? $this->arabic->visual($text) : $text;
        while ($size > 11 && $this->width($visual, $size, $font) > $maxWidth) {
            $size--;
        }
        imagettftext($image, $size, 0, $x, $baseline, $color, $font, $visual);
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
