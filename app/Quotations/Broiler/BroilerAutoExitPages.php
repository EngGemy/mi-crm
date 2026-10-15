<?php

namespace App\Quotations\Broiler;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Support\SiloCapacity;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * يبقي صفحات ملف تسمين التخريج الآلي، ويبدّل خانات الفورم فقط.
 */
class BroilerAutoExitPages
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function pdfBytes(PoultryQuotation $quotation): string
    {
        $docx = $this->fill($quotation);

        try {
            return $this->exportPdf($docx);
        } finally {
            @unlink($docx);
        }
    }

    public function fill(PoultryQuotation $quotation): string
    {
        $source = base_path('docs/quotations/broiler/broiler_auto_exit.docx');
        if (! is_file($source)) {
            throw new RuntimeException('ملف عرض تسمين التخريج الآلي غير موجود.');
        }

        $directory = storage_path('app/quotations/tmp');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('تعذر إنشاء مجلد مؤقت لعرض التسمين.');
        }

        $copy = $directory.DIRECTORY_SEPARATOR.'broiler-auto-'.uniqid('', true).'.docx';
        if (! copy($source, $copy)) {
            throw new RuntimeException('تعذر نسخ صفحات عرض التسمين.');
        }

        $this->patch($copy, $this->dataFrom($quotation));

        return $copy;
    }

    /** @return array<string, mixed> */
    public function dataFrom(PoultryQuotation $quotation): array
    {
        $snapshot = is_array($quotation->pricing_snapshot) ? $quotation->pricing_snapshot : [];
        $technical = is_array($snapshot['technical'] ?? null) ? $snapshot['technical'] : [];
        $computed = is_array($snapshot['computed'] ?? null) ? $snapshot['computed'] : [];
        $financial = is_array($snapshot['financial'] ?? null) ? $snapshot['financial'] : [];

        $tiers = (int) ($quotation->tiers ?: ($technical['tiers'] ?? 0));
        $lines = (int) ($technical['lines'] ?? $quotation->lines ?? 0);
        $totalNests = (int) ($quotation->total_nests ?? $computed['total_nests'] ?? $technical['total_nests'] ?? 0);
        $nestsPerLine = (int) ($quotation->nests_per_line ?? $computed['nests_per_line'] ?? $technical['nests_per_line'] ?? 0);
        if ($nestsPerLine <= 0 && $lines > 0 && $totalNests > 0) {
            $nestsPerLine = intdiv($totalNests, $lines);
        }

        $birds = (int) ($quotation->bird_count ?? $computed['bird_count'] ?? $technical['total_birds'] ?? 0);
        $barns = max(1, (int) ($quotation->barns_count ?: 1));
        $selected = $quotation->siloCapacity?->label_ar;
        $silo = SiloCapacity::labelForBirds($birds * $barns, is_string($selected) ? $selected : null);

        $total = (float) ($quotation->total ?? $financial['total'] ?? 0);
        $rate = (float) ($quotation->exchange_rate ?: ($snapshot['exchange_rate'] ?? 0));
        $issued = $quotation->issued_at ?? $quotation->created_at;

        $weight = (float) ($quotation->bird_weight_kg ?? $technical['bird_weight_kg'] ?? 0);
        $birdsPerNest = (int) ($quotation->birds_per_nest ?? $technical['birds_per_nest'] ?? 0);
        $calc = new PoultryTechnicalCalculator;
        $batteryHeight = $this->autoExitBatteryHeight($quotation, $tiers);

        return [
            'client_name' => (string) ($quotation->client_name ?: '—'),
            'project_label' => PoultryProjectType::BroilerAutoExit->labelAr(),
            'length' => $this->meters($quotation->length),
            'width' => $this->meters($quotation->width),
            'height' => $this->meters($quotation->height),
            'location' => trim((string) ($quotation->client_location ?: $quotation->client_address ?: '')) ?: '—',
            'effective_length' => $this->meters($technical['effective_length'] ?? $computed['effective_length'] ?? null),
            'battery_height' => $this->fixedMeters($batteryHeight),
            'aisle' => $lines > 0 ? $calc->transferDistanceCm((float) $quotation->width, $lines).' سم' : '',
            'tiers' => $tiers > 0 ? $tiers.' أدوار' : null,
            'tier_count' => $tiers > 0 ? (string) $tiers : null,
            'lines' => $lines > 0 ? $lines.' خطوط' : null,
            'nests_per_line' => $nestsPerLine > 0 ? $this->grouped($nestsPerLine).' قفص' : null,
            'total_nests' => $totalNests > 0 ? $this->grouped($totalNests).' قفص' : null,
            'inner_belt' => $this->metersLabel($quotation->innerBeltLength?->label_ar, '12 متر'),
            'outer_belt' => $this->metersLabel($quotation->outerBeltLength?->label_ar, '8 متر'),
            'motor_power' => $this->horsepower((string) ($quotation->motorPower?->label_ar ?? '')),
            'silo' => $silo,
            'issued_at' => $issued?->format('d/m/Y') ?? '',
            'total_egp' => $this->grouped($total),
            'total_usd' => $rate > 0 ? $this->money($total / $rate) : '',
            'care' => $this->careRows($snapshot, $weight, $birdsPerNest, $birds, $totalNests),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function patch(string $docxPath, array $data): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('تعذر فتح نسخة عرض التسمين.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('نص صفحات العرض غير موجود داخل الملف.');
        }

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS);

        $this->patchParagraphToken($xpath, 'بطول', [
            '12 متر' => (string) $data['inner_belt'],
            '8 متر' => (string) $data['outer_belt'],
        ]);
        $this->replaceParagraph($xpath, '81 متر', (string) $data['length']);
        $this->replaceParagraph($xpath, '12 متر', (string) $data['width']);
        $this->replaceExact($this->textNodes($xpath), '11 طن', (string) $data['silo']);

        $this->replaceExact($this->textNodes($xpath), '72 متر', (string) ($data['effective_length'] ?: '72 متر'));
        $this->replaceExact($this->textNodes($xpath), '4 أدوار', (string) ($data['tiers'] ?: '4 أدوار'));
        $this->replaceExact($this->textNodes($xpath), '4 خطوط', (string) ($data['lines'] ?: '4 خطوط'));
        $this->replaceExact($this->textNodes($xpath), '576 قفص', (string) ($data['nests_per_line'] ?: '576 قفص'));
        $this->replaceExact($this->textNodes($xpath), '2,304 قفص', (string) ($data['total_nests'] ?: '2,304 قفص'));
        $this->replaceExact($this->textNodes($xpath), '3.73 متر', (string) ($data['battery_height'] ?: '3.73 متر'));
        if (($data['aisle'] ?? '') !== '') {
            $this->replaceExact($this->textNodes($xpath), '102 سم', (string) $data['aisle']);
        }

        if ($data['tier_count'] !== null) {
            $this->patchManureCounts($xpath, (string) $data['tier_count']);
            $this->replaceContaining($this->textNodes($xpath), 'عدد 3 ناقل', 'عدد '.$data['tier_count'].' ناقل');
        }
        if (is_string($data['motor_power']) && $data['motor_power'] !== '') {
            $this->patchWords($this->textNodes($xpath), ['1.5', 'حصان'], [0 => (string) $data['motor_power']]);
        }

        $this->patchCare($this->textNodes($xpath), $data['care']);

        $nodes = $this->textNodes($xpath);
        $this->replaceExact($nodes, 'EHAB', (string) $data['client_name']);
        $this->replaceExact($nodes, '1/1/1', (string) ($data['issued_at'] ?: '1/1/1'));
        $this->replaceExact($nodes, '5,197,824', (string) $data['total_egp']);
        if ($data['total_usd'] !== '') {
            $this->replaceExact($nodes, '$', (string) $data['total_usd']);
        }
        $this->replaceContaining($nodes, 'بطاريات تسمين', 'بطاريات '.(string) $data['project_label']);
        $this->patchFirstSiloWord($nodes, (string) $data['silo']);

        $this->setRowValue($xpath, 'نوع العنبر', (string) $data['project_label']);
        $this->setRowValue($xpath, 'طول العنبر', (string) $data['length']);
        $this->setRowValue($xpath, 'عرض العنبر', (string) $data['width']);
        $this->setRowValue($xpath, 'ارتفاع العنبر', (string) $data['height']);
        $this->setRowValue($xpath, 'مكان العنبر', (string) $data['location']);

        $saved = $dom->saveXML();
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $saved === false ? $xml : $saved);
        $zip->close();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array{title: string, birds: string, total_label: string, total: string, area: string, feeding: string}>
     */
    private function careRows(array $snapshot, float $selectedWeight, int $snapshotBirds, int $snapshotTotal, int $cagesTotal): array
    {
        $map = $snapshot['parameters']['broiler_weight_birds_map'] ?? PoultryTechnicalCalculator::DEFAULT_BROILER_WEIGHT_MAP;
        $rows = [];
        foreach ((array) $map as $weight => $birds) {
            $birds = (int) $birds;
            if ($birds <= 0) {
                continue;
            }
            $rows[] = ['weight' => (float) $weight, 'birds' => $birds];
        }
        usort($rows, fn (array $a, array $b) => $a['weight'] <=> $b['weight']);

        if ($selectedWeight > 0 && $snapshotBirds > 0) {
            $known = false;
            foreach ($rows as $row) {
                if (abs($row['weight'] - $selectedWeight) < 0.001) {
                    $known = true;
                    break;
                }
            }
            if (! $known) {
                $rows[] = ['weight' => $selectedWeight, 'birds' => $snapshotBirds];
                usort($rows, fn (array $a, array $b) => $a['weight'] <=> $b['weight']);
            }
        }

        $selectedIndex = 0;
        $nearest = PHP_FLOAT_MAX;
        foreach ($rows as $i => $row) {
            $diff = abs($row['weight'] - $selectedWeight);
            if ($diff < $nearest) {
                $nearest = $diff;
                $selectedIndex = $i;
            }
        }
        $start = max(0, min($selectedIndex - 1, count($rows) - 3));
        $window = array_slice($rows, $start, 3);
        $feedingMap = (array) config('mi_proposal.feeding_zone_cm', []);
        $cageArea = (float) config('mi_proposal.specs.cage_area_cm2', 6500);
        $cageLength = (float) config('mi_proposal.specs.cage_length_cm', 100);

        $care = [];
        foreach ($window as $row) {
            $isSelected = abs($row['weight'] - $selectedWeight) < 0.001;
            $birds = (int) $row['birds'];
            $total = $cagesTotal * $birds;
            if ($isSelected && $snapshotBirds > 0) {
                $birds = $snapshotBirds;
                $total = $snapshotTotal > 0 ? $snapshotTotal : $cagesTotal * $birds;
            }
            $weightLabel = number_format((float) $row['weight'], 3, '.', '');
            $feeding = isset($feedingMap[$birds]) ? (float) $feedingMap[$birds] : ($birds > 0 ? round($cageLength / $birds, 2) : 0);
            $area = $birds > 0 ? (int) round($cageArea / $birds) : 0;
            $care[] = [
                'title' => 'الرعاية لكل طائر على متوسط وزن '.$weightLabel.' كجم',
                'birds' => $birds.' طائر',
                'total_label' => 'عدد القطع بالعنبر لوزن '.$weightLabel.' كجم',
                'total' => $this->grouped($total).' طائر',
                'area' => $this->grouped($area).' سم²',
                'feeding' => $this->plain($feeding).' سم',
            ];
        }

        return $care;
    }

    /** @param  list<array{title: string, birds: string, total_label: string, total: string, area: string, feeding: string}>  $care */
    private function patchCare(array $nodes, array $care): void
    {
        $template = [
            ['الرعاية لكل طائر على متوسط وزن 1.850 كجم', '18 طائر', '41,472 طائر', 'عدد القطع بالعنبر لوزن 1.850 كجم', '361 سم²', '5.5 سم'],
            ['الرعاية لكل طائر على متوسط وزن 2.100 كجم', '16 طائر', '36,864 طائر', 'عدد القطع بالعنبر لوزن 2.100 كجم', '406 سم²', '6.25 سم'],
            ['الرعاية لكل طائر على متوسط وزن 2.550 كجم', '13 طائر', '29,925 طائر', 'عدد القطع بالعنبر لوزن 2.100 كجم', '500 سم²', '7.7 سم'],
        ];
        $keys = ['title', 'birds', 'total', 'total_label', 'area', 'feeding'];
        $pairs = [];
        foreach ($template as $index => $from) {
            if (! isset($care[$index])) {
                break;
            }
            foreach ($keys as $keyIndex => $key) {
                $pairs[] = [$from[$keyIndex], $care[$index][$key]];
            }
        }

        $cursor = 0;
        foreach ($nodes as $node) {
            if ($cursor >= count($pairs)) {
                break;
            }
            if (trim($node->textContent) === $pairs[$cursor][0]) {
                $node->textContent = $this->keepSpace($node->textContent, $pairs[$cursor][1]);
                $cursor++;
            }
        }
    }

    /** @param  list<DOMElement>  $nodes */
    private function patchFirstSiloWord(array $nodes, string $capacity): void
    {
        $previous = '';
        foreach ($nodes as $node) {
            $text = $node->textContent;
            if (trim($text) === 'ايلو' && str_ends_with(trim($previous), '+ س')) {
                $node->textContent = 'ايلو سعة '.$capacity;
            }
            if (trim($text) !== '') {
                $previous = $text;
            }
        }
    }

    /**
     * عدد سيور السبلة يساوي عدد المواتير، وكلاهما عدد الأدوار.
     * القالب ممكن يكون مكتوب 3 أو 4، فنبّدل أي رقم ظاهر قبل سيور/ماتور.
     */
    private function patchManureCounts(DOMXPath $xpath, string $count): void
    {
        foreach ($xpath->query('//w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $text = '';
            $nodes = [];
            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                if ($node instanceof DOMElement) {
                    $nodes[] = $node;
                    $text .= $node->textContent;
                }
            }

            if (! str_contains($text, 'سيور السبلة') && ! str_contains($text, 'لتشغيل')) {
                continue;
            }

            foreach ($nodes as $node) {
                $value = $node->textContent;
                $updated = preg_replace(
                    [
                        '/عدد\s*[0-9]+(\s*)ماتور/u',
                        '/[0-9]+(\s*)سيور/u',
                    ],
                    [
                        'عدد '.$count.'$1ماتور',
                        $count.'$1سيور',
                    ],
                    $value,
                );
                if (is_string($updated) && $updated !== $value) {
                    $node->textContent = $updated;
                }
            }

            foreach (['3', '4', '5'] as $old) {
                $this->patchWords($nodes, [$old, 'ماتور'], [0 => $count]);
                $this->patchWords($nodes, [$old, 'سيور'], [0 => $count]);
            }
        }
    }

    /** @param  array<string, string>  $replacements */
    private function patchParagraphToken(DOMXPath $xpath, string $needle, array $replacements): void
    {
        foreach ($xpath->query('//w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }
            $text = '';
            $nodes = [];
            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                if ($node instanceof DOMElement) {
                    $nodes[] = $node;
                    $text .= $node->textContent;
                }
            }
            if (! str_contains($text, $needle)) {
                continue;
            }
            foreach ($nodes as $node) {
                $token = trim($node->textContent);
                if (isset($replacements[$token]) && $replacements[$token] !== $token) {
                    $node->textContent = $this->keepSpace($node->textContent, $replacements[$token]);
                }
            }
        }
    }

    private function replaceParagraph(DOMXPath $xpath, string $from, string $to): void
    {
        if ($to === '' || $to === $from) {
            return;
        }

        foreach ($xpath->query('//w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }
            $text = '';
            $nodes = [];
            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                if ($node instanceof DOMElement) {
                    $nodes[] = $node;
                    $text .= $node->textContent;
                }
            }
            if (trim($text) !== $from) {
                continue;
            }
            foreach ($nodes as $node) {
                if (trim($node->textContent) === $from) {
                    $node->textContent = $this->keepSpace($node->textContent, $to);
                }
            }
        }
    }

    private function setRowValue(DOMXPath $xpath, string $label, string $value): void
    {
        if ($value === '') {
            return;
        }

        foreach ($xpath->query('//w:tc') as $cell) {
            if (! $cell instanceof DOMElement) {
                continue;
            }
            $text = '';
            foreach ($xpath->query('.//w:t', $cell) as $node) {
                $text .= $node->textContent;
            }
            if (trim($text) !== $label) {
                continue;
            }
            $row = $cell->parentNode;
            if (! $row instanceof DOMElement) {
                continue;
            }
            $valueCell = null;
            foreach ($row->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'tc') {
                    $valueCell = $child;
                    break;
                }
            }
            if (! $valueCell instanceof DOMElement || $valueCell === $cell) {
                continue;
            }
            $this->writeCell($valueCell, $value);
        }
    }

    private function writeCell(DOMElement $cell, string $value): void
    {
        $existing = $cell->getElementsByTagNameNS(self::NS, 't')->item(0);
        if ($existing instanceof DOMElement) {
            $existing->textContent = $value;

            return;
        }

        $paragraph = $cell->getElementsByTagNameNS(self::NS, 'p')->item(0);
        if (! $paragraph instanceof DOMElement) {
            return;
        }

        $dom = $cell->ownerDocument;
        if (! $dom instanceof DOMDocument) {
            return;
        }

        $run = $dom->createElementNS(self::NS, 'w:r');
        $text = $dom->createElementNS(self::NS, 'w:t');
        $text->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $text->textContent = $value;
        $run->appendChild($text);
        $paragraph->appendChild($run);
    }

    /** @return list<DOMElement> */
    private function textNodes(DOMXPath $xpath): array
    {
        $nodes = [];
        foreach ($xpath->query('//w:t') as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /** @param  list<DOMElement>  $nodes */
    private function replaceExact(array $nodes, string $from, string $to): void
    {
        if ($to === '' || $to === $from) {
            return;
        }

        foreach ($nodes as $node) {
            if (trim($node->textContent) === $from) {
                $node->textContent = $this->keepSpace($node->textContent, $to);
            }
        }
    }

    /** @param  list<DOMElement>  $nodes */
    private function replaceContaining(array $nodes, string $from, string $to): void
    {
        if ($from === '' || $to === $from) {
            return;
        }

        foreach ($nodes as $node) {
            if (str_contains($node->textContent, $from)) {
                $node->textContent = str_replace($from, $to, $node->textContent);
            }
        }
    }

    /**
     * @param  list<DOMElement>  $nodes
     * @param  list<string>  $sequence
     * @param  array<int, string>  $slots
     */
    private function patchWords(array $nodes, array $sequence, array $slots): void
    {
        $filled = [];
        foreach ($nodes as $index => $node) {
            if (trim($node->textContent) !== '') {
                $filled[] = $index;
            }
        }

        $size = count($sequence);
        $last = count($filled) - $size;
        for ($cursor = 0; $cursor <= $last; $cursor++) {
            $matches = true;
            foreach ($sequence as $offset => $text) {
                if (trim($nodes[$filled[$cursor + $offset]]->textContent) !== $text) {
                    $matches = false;
                    break;
                }
            }
            if (! $matches) {
                continue;
            }
            foreach ($slots as $offset => $value) {
                $node = $nodes[$filled[$cursor + (int) $offset]];
                $node->textContent = $this->keepSpace($node->textContent, $value);
            }
        }
    }

    private function exportPdf(string $docxPath): string
    {
        $libre = $this->viaLibreOffice($docxPath);
        if ($libre !== null) {
            return $libre;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('تعذر تصدير عرض التسمين. LibreOffice غير متاح على السيرفر.');
        }

        $pdfPath = $docxPath.'.pdf';
        $scriptPath = $docxPath.'.ps1';
        $docx = str_replace("'", "''", $docxPath);
        $pdf = str_replace("'", "''", $pdfPath);
        file_put_contents($scriptPath, <<<PS1
\$ErrorActionPreference = 'Stop'
\$word = \$null
\$doc = \$null
try {
  \$word = New-Object -ComObject Word.Application
  \$word.Visible = \$false
  \$word.DisplayAlerts = 0
  \$doc = \$word.Documents.Open('{$docx}', \$false, \$true, \$false)
  \$doc.ExportAsFixedFormat('{$pdf}', 17)
} catch {
  [Console]::Error.WriteLine(\$_.Exception.Message)
  exit 1
} finally {
  if (\$doc -ne \$null) { \$doc.Close(0) }
  if (\$word -ne \$null) { \$word.Quit() }
}
PS1);

        $detail = 'تأكد أن Microsoft Word مثبت.';
        $code = 1;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            @unlink($pdfPath);
            $output = [];
            exec('powershell -NoProfile -STA -NonInteractive -ExecutionPolicy Bypass -File '.escapeshellarg($scriptPath).' 2>&1', $output, $code);
            if ($code === 0 && is_file($pdfPath)) {
                break;
            }
            $detail = trim(implode(' ', $output)) ?: $detail;
            if ($attempt < 3) {
                sleep(2);
            }
        }
        @unlink($scriptPath);

        if ($code !== 0 || ! is_file($pdfPath)) {
            throw new RuntimeException('تعذر تصدير صفحات ملف الوورد. '.$detail);
        }

        $bytes = file_get_contents($pdfPath);
        @unlink($pdfPath);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF')) {
            throw new RuntimeException('ملف PDF الأصلي لم يُنشأ بشكل صحيح.');
        }

        return $bytes;
    }

    private function viaLibreOffice(string $docxPath): ?string
    {
        $binary = PHP_OS_FAMILY === 'Windows'
            ? (is_file('C:\\Program Files\\LibreOffice\\program\\soffice.exe')
                ? 'C:\\Program Files\\LibreOffice\\program\\soffice.exe'
                : (is_file('C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe')
                    ? 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe'
                    : null))
            : null;

        if ($binary === null && PHP_OS_FAMILY !== 'Windows') {
            exec('command -v soffice', $output, $code);
            $binary = $code === 0 && isset($output[0]) && $output[0] !== '' ? trim($output[0]) : null;
        }

        if ($binary === null) {
            return null;
        }

        $outDir = dirname($docxPath);
        exec(
            escapeshellarg($binary).' --headless --norestore --convert-to pdf --outdir '.escapeshellarg($outDir).' '.escapeshellarg($docxPath),
            $converted,
            $status
        );

        $pdfPath = $outDir.DIRECTORY_SEPARATOR.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if ($status !== 0 || ! is_file($pdfPath)) {
            @unlink($pdfPath);

            return null;
        }

        $bytes = file_get_contents($pdfPath);
        @unlink($pdfPath);

        return is_string($bytes) && str_starts_with($bytes, '%PDF') ? $bytes : null;
    }

    private function autoExitBatteryHeight(PoultryQuotation $quotation, int $tiers): float
    {
        $start = (new PoultryTechnicalCalculator)->autoExitHeightStart($tiers > 0 ? $tiers : 3);
        $typed = $quotation->battery_height_m;
        if (is_numeric($typed) && (float) $typed + 0.0001 >= $start) {
            return (float) $typed;
        }

        return $start;
    }

    private function fixedMeters(float $value): string
    {
        return number_format($value, 2, '.', '').' متر';
    }

    private function meters(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        return $this->plain((float) $value).' متر';
    }

    private function metersLabel(?string $label, string $fallback): string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return $fallback;
        }

        return str_contains($label, 'متر') ? $label : $label.' متر';
    }

    private function horsepower(string $label): ?string
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)/u', $label, $match) !== 1) {
            return null;
        }

        return str_replace(',', '.', $match[1]);
    }

    private function grouped(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    private function money(float $value): string
    {
        $rounded = round($value, 2);
        if (abs($rounded - round($rounded)) < 0.001) {
            return $this->grouped($rounded);
        }

        return number_format($rounded, 2, '.', ',');
    }

    private function plain(float $value): string
    {
        if (abs($value - round($value)) < 0.001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function keepSpace(string $original, string $replacement): string
    {
        preg_match('/^\s*/u', $original, $lead);
        preg_match('/\s*$/u', $original, $trail);

        return ($lead[0] ?? '').$replacement.($trail[0] ?? '');
    }
}
