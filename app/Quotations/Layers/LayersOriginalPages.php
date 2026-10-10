<?php

namespace App\Quotations\Layers;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Quotations\Templates\LayersQuotationTemplate;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * يبقي صفحات ملف البياض الأصلية، ويبدّل خانات البيانات فقط.
 */
class LayersOriginalPages
{
    public function fill(PoultryQuotation $quotation): string
    {
        $source = base_path('docs/quotations/layers/layers_quotation.docx');
        if (! is_file($source)) {
            throw new RuntimeException('ملف صفحات البياض الأصلي غير موجود.');
        }

        $directory = storage_path('app/quotations/tmp');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('تعذر إنشاء مجلد مؤقت لعرض البياض.');
        }

        $copy = $directory.DIRECTORY_SEPARATOR.'layers-'.uniqid('', true).'.docx';
        if (! copy($source, $copy)) {
            throw new RuntimeException('تعذر نسخ صفحات العرض الأصلية.');
        }

        $data = (new LayersQuotationTemplate)->buildData($quotation);
        $this->patch($copy, $data);

        return $copy;
    }

    public function exportPdf(string $docxPath): string
    {
        $libre = $this->viaLibreOffice($docxPath);
        if ($libre !== null) {
            return $libre;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('تعذر تصدير صفحات العرض الأصلية. LibreOffice غير متاح على السيرفر.');
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
        $binary = $this->libreOfficeBinary();
        if ($binary === null) {
            return null;
        }

        $outDir = dirname($docxPath);
        exec(
            escapeshellarg($binary).' --headless --norestore --convert-to pdf --outdir '.escapeshellarg($outDir).' '.escapeshellarg($docxPath),
            $output,
            $code
        );

        $pdfPath = $outDir.DIRECTORY_SEPARATOR.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if ($code !== 0 || ! is_file($pdfPath)) {
            @unlink($pdfPath);

            return null;
        }

        $bytes = file_get_contents($pdfPath);
        @unlink($pdfPath);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF')) {
            return null;
        }

        return $bytes;
    }

    private function libreOfficeBinary(): ?string
    {
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ]
            : ['soffice', 'libreoffice'];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        exec('command -v soffice', $output, $code);

        return $code === 0 && isset($output[0]) && $output[0] !== '' ? trim($output[0]) : null;
    }

    /** @param  array<string, mixed>  $data */
    private function patch(string $docxPath, array $data): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('تعذر فتح نسخة عرض البياض.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('نص صفحات العرض غير موجود داخل الملف.');
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        if (($data['project_type'] ?? '') !== PoultryProjectType::LayerAutoCollect->value) {
            $this->dropEggCollectionPage($xpath);
        }

        $nodes = [];
        foreach ($xpath->query('//w:t') as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        $this->replaceContaining($nodes, 'م/ محمد جامع', $this->text($data['client_name'] ?? null, 'م/ محمد جامع'));
        $this->replaceExact($nodes, '01/10/2026', $this->text($data['issued_at'] ?? null));
        $this->replaceExact($nodes, 'بياض', $this->barnType((string) ($data['project_type'] ?? '')));
        $this->replaceExact($nodes, '81', $this->comma($data['length'] ?? null, '81'));
        $this->replaceExact($nodes, '11,5', $this->comma($data['width'] ?? null, '11,5'));
        $this->replaceExact($nodes, '3,5', $this->comma($data['height'] ?? null, '3,5'));
        $this->replaceExact($nodes, 'دمياط', $this->text($data['location'] ?? null, 'دمياط'));
        $this->replaceExact($nodes, '130,560', $this->money($data['financial']['barn_usd'] ?? null, '130,560'));
        $this->replaceExact($nodes, '6,789,120', $this->money($data['financial']['barn_egp'] ?? null, '6,789,120'));
        $this->replaceExact($nodes, '3,840', $this->grouped($data['total_nests'] ?? null, '3,840'));
        $this->replaceExact($nodes, '38,400 طائر', $this->withUnit($data['bird_count'] ?? null, 'طائر', '38,400 طائر'));
        $silo = $this->text($data['silo_capacity'] ?? null, '11 طن');
        $siloCount = (int) ($data['silos_count'] ?? 1);
        if ($siloCount > 1) {
            $silo = $siloCount.' × '.$silo;
        }
        $this->replaceExact($nodes, '11 طن', $silo);
        $this->replaceExact($nodes, '12 متر', $this->text($data['inner_belt'] ?? null, '12 متر'));
        $this->replaceExact($nodes, '8 متر', $this->text($data['outer_belt'] ?? null, '8 متر'));
        $this->replaceExact($nodes, '1.5 حصان', $this->text($data['motor_power'] ?? null, '1.5 حصان'));
        $motors = $this->leadingInteger($data['manure_motor_count'] ?? null);
        if ($motors !== null) {
            $this->patchWords($nodes, ['- عدد', '1', 'ماتور'], [1 => $motors]);
        }
        $belts = $this->leadingInteger($data['belts_per_line'] ?? null);
        if ($belts !== null) {
            $this->patchWords($nodes, ['4', 'سيور'], [0 => $belts]);
        }

        $this->patchSlots($nodes, ['متر', '7', '2'], [
            1 => $this->optionalComma($data['effective_length'] ?? null),
            2 => $this->hasNumber($data['effective_length'] ?? null) ? '' : null,
        ]);
        $this->patchSlots($nodes, ['4', 'أدوار'], [0 => $this->optionalComma($data['tiers'] ?? null)]);
        $this->patchSlots($nodes, ['4', 'خطوط'], [0 => $this->optionalComma($data['lines'] ?? null)]);
        $this->patchSlots($nodes, ['120', 'قفص'], [0 => $this->optionalGrouped($data['nests_one_side'] ?? null)]);
        $this->patchSlots($nodes, ['10', 'طائر'], [0 => $this->optionalComma($data['birds_per_nest'] ?? null)]);
        $this->patchSlots($nodes, ['10', 'طيور في العش'], [0 => $this->optionalComma($data['birds_per_nest'] ?? null)]);
        $power = $this->horsepowerParts($data['motor_power'] ?? null);
        if ($power !== null) {
            $this->patchWords($nodes, ['1', '.5', 'حصان'], [0 => $power[0], 1 => $power[1]]);
        }
        $this->patchStocking($nodes, $data['stocking']['area_cm2'] ?? null, $data['stocking']['feeding_cm'] ?? null);

        $saved = $dom->saveXML();
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $saved === false ? $xml : $saved);
        $zip->close();
    }

    private function dropEggCollectionPage(DOMXPath $xpath): void
    {
        $remove = [];
        foreach ($xpath->query('//w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $text = '';
            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                $text .= $node->textContent;
            }

            if (str_contains($text, 'دولاب البيض') || str_contains($text, 'Eggs Collection') || str_contains($text, 'تتم عملية جمع البيض')) {
                $remove[] = $paragraph;
            }
        }

        foreach ($remove as $paragraph) {
            $paragraph->parentNode?->removeChild($paragraph);
        }
    }

    /** @param  list<DOMElement>  $nodes */
    private function replaceExact(array $nodes, string $from, string $to): void
    {
        if ($to === $from) {
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
     * يطابق الكلمات حتى لو فصلها وورد بخانات فارغة.
     *
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

            foreach ($slots as $offset => $text) {
                $node = $nodes[$filled[$cursor + $offset]];
                $node->textContent = $text === ''
                    ? ''
                    : $this->keepSpace($node->textContent, $text);
            }
            $cursor += $size - 1;
        }
    }

    /** @param  list<DOMElement>  $nodes
     * @param  list<string>  $sequence
     * @param  array<int, ?string>  $slots
     */
    private function patchSlots(array $nodes, array $sequence, array $slots): void
    {
        if (in_array(null, $slots, true)) {
            return;
        }

        $from = 0;
        $size = count($sequence);
        while (($index = $this->findSequence($nodes, $sequence, $from)) !== null) {
            foreach ($slots as $offset => $text) {
                $nodes[$index + $offset]->textContent = $text === ''
                    ? ''
                    : $this->keepSpace($nodes[$index + $offset]->textContent, $text);
            }
            $from = $index + $size;
        }
    }

    /** @param  list<DOMElement>  $nodes
     * @param  list<string>  $sequence
     */
    private function findSequence(array $nodes, array $sequence, int $from): ?int
    {
        $last = count($nodes) - count($sequence);
        for ($index = $from; $index <= $last; $index++) {
            $matches = true;
            foreach ($sequence as $offset => $text) {
                if (trim($nodes[$index + $offset]->textContent) !== $text) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return $index;
            }
        }

        return null;
    }

    /** @param  list<DOMElement>  $nodes */
    private function patchStocking(array $nodes, mixed $area, mixed $feeding): void
    {
        if ($this->hasNumber($area)) {
            $from = 0;
            while (($index = $this->findSequence($nodes, ['3', '90', 'سم²'], $from)) !== null) {
                $nodes[$index]->textContent = $this->keepSpace($nodes[$index]->textContent, $this->grouped($area, '390'));
                $nodes[$index + 1]->textContent = '';
                $from = $index + 3;
            }
        }

        if (! $this->hasNumber($feeding)) {
            return;
        }

        $value = $this->comma($feeding, '6');
        $last = count($nodes) - 3;
        for ($index = 0; $index <= $last; $index++) {
            if (trim($nodes[$index]->textContent) !== '6' || trim($nodes[$index + 1]->textContent) !== 'سم') {
                continue;
            }
            $near = false;
            for ($look = 2; $look <= 4 && $index + $look < count($nodes); $look++) {
                if (trim($nodes[$index + $look]->textContent) === 'منطقة') {
                    $near = true;
                    break;
                }
            }
            if ($near) {
                $nodes[$index]->textContent = $this->keepSpace($nodes[$index]->textContent, $value);
            }
        }
    }

    private function barnType(string $projectType): string
    {
        return match ($projectType) {
            PoultryProjectType::Layer->value => 'بياض',
            PoultryProjectType::LayerAutoCollect->value => 'بياض جمع آلي',
            PoultryProjectType::LayerRearing->value => 'تربية بياض',
            default => PoultryProjectType::tryFrom($projectType)?->labelAr() ?: 'بياض',
        };
    }

    /** @return array{0: string, 1: string}|null */
    private function horsepowerParts(mixed $label): ?array
    {
        if (! is_string($label) || trim($label) === '' || trim($label) === '1.5 حصان') {
            return null;
        }

        if (! preg_match('/(\d+)(?:\.(\d+))?/u', $label, $match)) {
            return null;
        }

        $fraction = ($match[2] ?? '') !== '' ? '.'.$match[2] : '';

        return [$match[1], $fraction];
    }

    private function leadingInteger(mixed $label): ?string
    {
        if (! is_string($label) || ! preg_match('/(\d+)/u', $label, $match)) {
            return null;
        }

        return $match[1];
    }

    private function text(mixed $value, string $fallback = '—'): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : $fallback;
    }

    private function optionalComma(mixed $value): ?string
    {
        return $this->hasNumber($value) ? $this->comma($value, '') : null;
    }

    private function optionalGrouped(mixed $value): ?string
    {
        return $this->hasNumber($value) ? $this->grouped($value, '') : null;
    }

    private function comma(mixed $value, string $fallback): string
    {
        if (! $this->hasNumber($value)) {
            return $fallback;
        }

        $number = round((float) $value, 2);
        if (abs($number - round($number)) < 0.001) {
            return (string) (int) round($number);
        }

        $formatted = number_format($number, 2, ',', '');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    private function grouped(mixed $value, string $fallback): string
    {
        if (! $this->hasNumber($value)) {
            return $fallback;
        }

        $number = (float) $value;
        $decimals = abs($number - round($number)) < 0.001 ? 0 : 2;

        return number_format($number, $decimals, '.', ',');
    }

    private function money(mixed $value, string $fallback): string
    {
        return $this->grouped($value, $fallback);
    }

    private function withUnit(mixed $value, string $unit, string $fallback): string
    {
        if (! $this->hasNumber($value)) {
            return $fallback;
        }

        return $this->grouped($value, $fallback).' '.$unit;
    }

    private function keepSpace(string $original, string $replacement): string
    {
        preg_match('/^\s*/u', $original, $lead);
        preg_match('/\s*$/u', $original, $trail);

        return ($lead[0] ?? '').$replacement.($trail[0] ?? '');
    }

    private function hasNumber(mixed $value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }
}
