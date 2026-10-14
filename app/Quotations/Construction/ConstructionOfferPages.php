<?php

namespace App\Quotations\Construction;

use App\Models\PoultryQuotation;
use App\Quotations\Pdf\DocxToPdf;
use App\Services\Poultry\ConstructionSummary;
use App\Services\Poultry\VentilationOffer;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ZipArchive;

/**
 * يملأ تصميم عرض الإنشاءات من أبعاد العنبر وأسعار الإعدادات.
 */
class ConstructionOfferPages
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function httpResponse(PoultryQuotation $quotation): Response
    {
        $name = 'construction-'.($quotation->quote_number ?: $quotation->getKey()).'.pdf';

        return response($this->pdfBytes($quotation), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    public function pdfBytes(PoultryQuotation $quotation): string
    {
        $docx = null;
        try {
            $docx = $this->fill($quotation);

            return app(DocxToPdf::class)->convert($docx);
        } catch (Throwable $e) {
            report($e);

            return app(ConstructionBrochurePdf::class)->render($quotation);
        } finally {
            if (is_string($docx) && is_file($docx)) {
                @unlink($docx);
            }
        }
    }

    public function fill(PoultryQuotation $quotation): string
    {
        $source = base_path('docs/quotations/construction/construction_offer.docx');
        if (! is_file($source)) {
            throw new RuntimeException('ملف عرض الإنشاءات غير موجود.');
        }

        $directory = storage_path('app/quotations/tmp');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('تعذر إنشاء مجلد مؤقت لعرض الإنشاءات.');
        }

        $copy = $directory.DIRECTORY_SEPARATOR.'construction-'.uniqid('', true).'.docx';
        if (! copy($source, $copy)) {
            throw new RuntimeException('تعذر نسخ عرض الإنشاءات.');
        }

        $this->patch($copy, $quotation);

        return $copy;
    }

    private function patch(string $docxPath, PoultryQuotation $quotation): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('تعذر فتح نسخة عرض الإنشاءات.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('نص عرض الإنشاءات غير موجود داخل الملف.');
        }

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS);

        $barn = app(VentilationOffer::class)->barnLabel(
            \App\Enums\PoultryProjectType::tryFrom((string) $quotation->project_type)
        );
        $this->writeLabeledRow($xpath, 'نوع العنبر', $barn);
        $this->writeLabeledRow($xpath, 'طول العنبر', VentilationOffer::plain((float) $quotation->length).' متر');
        $this->writeLabeledRow($xpath, 'عرض العنبر', VentilationOffer::plain((float) $quotation->width).' متر');
        $this->writeLabeledRow($xpath, 'ارتفاع العنبر', VentilationOffer::plain((float) $quotation->height).' متر');

        $lines = app(ConstructionSummary::class)->brochure($quotation);
        $this->writeMoneyRow($xpath, 'اعمال الخرسانة', $lines['concrete']);
        $this->writeMoneyRow($xpath, 'المبنى الاستيل', $lines['steel']);
        $this->writeMoneyRow($xpath, 'الحوائط عبارة', $lines['walls']);
        $this->writeMoneyRow($xpath, 'خزانات مياة الشرب', $lines['tanks']);
        $this->writeTotal($xpath, $lines['total']);

        $saved = $dom->saveXML();
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $saved === false ? $xml : $saved);
        $zip->close();
    }

    private function writeLabeledRow(DOMXPath $xpath, string $label, string $value): void
    {
        foreach ($this->rows($xpath) as $row) {
            $cells = $this->cells($row);
            if (count($cells) < 2 || ! str_contains($this->cellText($cells[0]), $label)) {
                continue;
            }
            $this->writeCell($cells[1], $value);
        }
    }

    /**
     * @param  array{qty: string, unit: string, amount: string}  $line
     */
    private function writeMoneyRow(DOMXPath $xpath, string $marker, array $line): void
    {
        foreach ($this->rows($xpath) as $row) {
            $cells = $this->cells($row);
            if ($cells === [] || ! str_contains($this->cellText($cells[0]), $marker)) {
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
            return;
        }

        $nodes[0]->textContent = $value;
        for ($index = 1, $count = count($nodes); $index < $count; $index++) {
            $nodes[$index]->textContent = '';
        }
    }
}
