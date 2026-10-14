<?php

namespace App\Quotations\Ventilation;

use App\Models\PoultryQuotation;
use App\Quotations\Pdf\DocxToPdf;
use App\Services\Poultry\VentilationOffer;
use Throwable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * يملأ عرض التهوية من أبعاد العنبر وحساب الشفاطات والتبريد والشبابيك.
 */
class VentilationOfferPages
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function httpResponse(PoultryQuotation $quotation): Response
    {
        $name = 'ventilation-'.($quotation->quote_number ?: $quotation->getKey()).'.pdf';

        return response($this->pdfBytes($quotation), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    public function accessoriesResponse(PoultryQuotation $quotation): Response
    {
        $name = 'accessories-'.($quotation->quote_number ?: $quotation->getKey()).'.pdf';

        return response($this->pdfBytes($quotation), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    public function constructionResponse(PoultryQuotation $quotation): Response
    {
        return app(\App\Quotations\Construction\ConstructionOfferPages::class)->httpResponse($quotation);
    }

    public function pdfBytes(PoultryQuotation $quotation): string
    {
        $docx = null;
        try {
            $docx = $this->fill($quotation);

            return app(DocxToPdf::class)->convert($docx);
        } catch (Throwable $e) {
            report($e);

            return app(VentilationBrochurePdf::class)->render($quotation);
        } finally {
            if (is_string($docx) && is_file($docx)) {
                @unlink($docx);
            }
        }
    }

    public function fill(PoultryQuotation $quotation): string
    {
        $source = base_path('docs/quotations/ventilation/ventilation_offer.docx');
        if (! is_file($source)) {
            throw new RuntimeException('ملف عرض التهوية غير موجود.');
        }

        $directory = storage_path('app/quotations/tmp');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('تعذر إنشاء مجلد مؤقت لعرض التهوية.');
        }

        $copy = $directory.DIRECTORY_SEPARATOR.'ventilation-'.uniqid('', true).'.docx';
        if (! copy($source, $copy)) {
            throw new RuntimeException('تعذر نسخ عرض التهوية.');
        }

        [$barnLabel, $offer] = app(VentilationOffer::class)->forQuotation($quotation);
        $this->patch($copy, $quotation, $barnLabel, $offer);

        return $copy;
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    private function patch(string $docxPath, PoultryQuotation $quotation, string $barnLabel, array $offer): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('تعذر فتح نسخة عرض التهوية.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('نص عرض التهوية غير موجود داخل الملف.');
        }

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS);

        $this->writeLabeledRow($xpath, 'نوع العنبر', $barnLabel);
        $this->writeLabeledRow($xpath, 'طول العنبر', $this->meters((float) $quotation->length));
        $this->writeLabeledRow($xpath, 'عرض العنبر', $this->meters((float) $quotation->width));
        $this->writeLabeledRow($xpath, 'ارتفاع العنبر', $this->meters((float) $quotation->height));

        $document = is_array($offer['document'] ?? null) ? $offer['document'] : [];
        $this->writeMoneyRow($xpath, 'شفاطات طرد', $document['fans'] ?? null);
        $this->writeMoneyRow($xpath, 'التبريد الخاص', $document['cooling'] ?? null);
        $this->writeMoneyRow($xpath, 'التدفئة المركزية', $document['heating'] ?? null);
        $this->writeMoneyRow($xpath, 'مراوح تقليب', $document['circulation'] ?? null);
        $this->writeMoneyRow($xpath, 'شبابيك تهوية', $document['inlets'] ?? null);
        $this->writeMoneyRow($xpath, 'MUNTER TRIO', $document['monitor'] ?? null);
        $this->writeMoneyRow($xpath, 'لوح الكنترول', $document['electricity'] ?? null);
        $this->writeTotal($xpath, (string) ($document['total'] ?? ''));

        $saved = $dom->saveXML();
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $saved === false ? $xml : $saved);
        $zip->close();
    }

    private function writeLabeledRow(DOMXPath $xpath, string $label, string $value): void
    {
        if ($value === '') {
            return;
        }

        foreach ($this->rows($xpath) as $row) {
            $cells = $this->cells($row);
            if (count($cells) < 2 || ! str_contains($this->cellText($cells[0]), $label)) {
                continue;
            }
            $this->writeCell($cells[1], $value);
        }
    }

    /**
     * @param  array{qty: string, unit: string, amount: string}|null  $line
     */
    private function writeMoneyRow(DOMXPath $xpath, string $marker, ?array $line): void
    {
        foreach ($this->rows($xpath) as $row) {
            $cells = $this->cells($row);
            if ($cells === [] || ! str_contains($this->cellText($cells[0]), $marker)) {
                continue;
            }
            if ($line === null) {
                $row->parentNode?->removeChild($row);

                continue;
            }
            if (isset($cells[1], $cells[3], $cells[5])) {
                $this->writeCell($cells[1], $line['unit']);
                $this->writeCell($cells[3], $line['qty']);
                $this->writeCell($cells[5], $line['amount']);
            }
        }
    }

    private function writeTotal(DOMXPath $xpath, string $total): void
    {
        if ($total === '') {
            return;
        }

        foreach ($this->rows($xpath) as $row) {
            $cells = $this->cells($row);
            if (count($cells) < 2 || ! str_contains($this->rowText($row), 'TOTAL')) {
                continue;
            }
            $this->writeCell($cells[count($cells) - 1], $total);
        }
    }

    /** @return list<DOMElement> */
    private function rows(DOMXPath $xpath): array
    {
        $rows = [];
        foreach ($xpath->query('//w:tr') as $row) {
            if ($row instanceof DOMElement) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return list<DOMElement> */
    private function cells(DOMElement $row): array
    {
        $cells = [];
        foreach ($row->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'tc') {
                $cells[] = $child;
            }
        }

        return $cells;
    }

    private function rowText(DOMElement $row): string
    {
        $text = '';
        foreach ($this->cells($row) as $cell) {
            $text .= $this->cellText($cell);
        }

        return $text;
    }

    private function cellText(DOMElement $cell): string
    {
        $text = '';
        foreach ($cell->getElementsByTagNameNS(self::NS, 't') as $node) {
            $text .= $node->textContent;
        }

        return $text;
    }

    private function writeCell(DOMElement $cell, string $value): void
    {
        $nodes = [];
        foreach ($cell->getElementsByTagNameNS(self::NS, 't') as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        if ($nodes === []) {
            $paragraph = $cell->getElementsByTagNameNS(self::NS, 'p')->item(0);
            $dom = $cell->ownerDocument;
            if (! $paragraph instanceof DOMElement || ! $dom instanceof DOMDocument) {
                return;
            }
            $run = $dom->createElementNS(self::NS, 'w:r');
            $text = $dom->createElementNS(self::NS, 'w:t');
            $text->textContent = $value;
            $run->appendChild($text);
            $paragraph->appendChild($run);

            return;
        }

        $nodes[0]->textContent = $value;
        for ($index = 1, $count = count($nodes); $index < $count; $index++) {
            $nodes[$index]->textContent = '';
        }
    }

    private function meters(float $value): string
    {
        return VentilationOffer::plain($value).' متر';
    }

    private function exportPdf(string $docxPath): string
    {
        $libre = $this->viaLibreOffice($docxPath);
        if ($libre !== null) {
            return $libre;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('تعذر تصدير عرض التهوية. LibreOffice غير متاح على السيرفر.');
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
            throw new RuntimeException('تعذر تصدير عرض التهوية. '.$detail);
        }

        $bytes = file_get_contents($pdfPath);
        @unlink($pdfPath);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF')) {
            throw new RuntimeException('ملف عرض التهوية لم يُنشأ بشكل صحيح.');
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
}
