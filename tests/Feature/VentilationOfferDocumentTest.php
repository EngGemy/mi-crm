<?php

namespace Tests\Feature;

use App\Models\PoultryQuotation;
use App\Quotations\Construction\ConstructionOfferPages;
use App\Quotations\Pdf\DocxToPdf;
use App\Quotations\Ventilation\VentilationOfferPages;
use RuntimeException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class VentilationOfferDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_broiler_ventilation_document_uses_the_calculated_quantities(): void
    {
        $quote = $this->quote('broiler', 114, 17, 4.1, 6);
        $cells = $this->cells((new VentilationOfferPages)->fill($quote));

        $this->assertSame('تسمين', $this->valueBeside($cells, 'نوع العنبر'));
        $this->assertSame('114 متر', $this->valueBeside($cells, 'طول العنبر'));
        $this->assertSame('17 متر', $this->valueBeside($cells, 'عرض العنبر'));
        $this->assertSame('4.1 متر', $this->valueBeside($cells, 'ارتفاع العنبر'));
        $this->assertStringContainsString('34', $this->row($cells, 'شفاطات طرد'));
        $this->assertStringContainsString('1,156,000', $this->row($cells, 'شفاطات طرد'));
        $this->assertStringContainsString('187', $this->row($cells, 'التبريد الخاص'));
        $this->assertStringContainsString('897,600', $this->row($cells, 'التبريد الخاص'));
        $this->assertStringContainsString('750,000', $this->row($cells, 'التدفئة المركزية'));
        $this->assertStringNotContainsString('إلى', $this->row($cells, 'التدفئة المركزية'));
        $this->assertStringContainsString('6', $this->row($cells, 'مراوح تقليب'));
        $this->assertStringContainsString('111,000', $this->row($cells, 'مراوح تقليب'));
        $this->assertStringContainsString('110', $this->row($cells, 'شبابيك تهوية'));
        $this->assertStringContainsString('253,000', $this->row($cells, 'شبابيك تهوية'));
        $this->assertStringContainsString('250,000', $this->row($cells, 'MUNTER TRIO'));
        $this->assertStringNotContainsString('260,000', $this->row($cells, 'MUNTER TRIO'));
        $this->assertStringContainsString('750,000', $this->row($cells, 'لوح الكنترول'));
        $this->assertStringContainsString('4,167,600', $this->row($cells, 'TOTAL'));
        $this->assertStringNotContainsString('إلى', $this->row($cells, 'TOTAL'));
    }

    public function test_layer_ventilation_document_omits_heating_and_side_fans(): void
    {
        $quote = $this->quote('layer', 81, 12, 4, 6);
        $cells = $this->cells((new VentilationOfferPages)->fill($quote));
        $flat = implode("\n", array_map(fn (array $row) => implode(' | ', $row), $cells));

        $this->assertSame('بياض', $this->valueBeside($cells, 'نوع العنبر'));
        $this->assertSame('81 متر', $this->valueBeside($cells, 'طول العنبر'));
        $this->assertStringContainsString('28', $this->row($cells, 'شفاطات طرد'));
        $this->assertStringContainsString('154', $this->row($cells, 'التبريد الخاص'));
        $this->assertStringContainsString('78', $this->row($cells, 'شبابيك تهوية'));
        $this->assertStringNotContainsString('التدفئة المركزية', $flat);
        $this->assertStringNotContainsString('مراوح تقليب', $flat);
        $this->assertStringContainsString('2,870,600', $this->row($cells, 'TOTAL'));
        $this->assertStringNotContainsString('إلى', $this->row($cells, 'TOTAL'));
    }

    public function test_accessories_pdf_falls_back_when_word_conversion_fails(): void
    {
        $this->app->instance(DocxToPdf::class, new class extends DocxToPdf
        {
            public function convert(string $docxPath): string
            {
                throw new RuntimeException('LibreOffice غير متاح على السيرفر.');
            }
        });

        $quote = $this->quote('broiler', 81, 12, 4, 4);
        $response = (new VentilationOfferPages)->accessoriesResponse($quote);
        $bytes = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertGreaterThan(500000, strlen($bytes));
        $this->assertSame(7, preg_match_all('/\/Type\s*\/Page(?![s])/', $bytes));
    }

    public function test_construction_document_keeps_the_brochure_and_fills_the_barn(): void
    {
        $quote = new PoultryQuotation([
            'project_type' => 'broiler',
            'length' => 81,
            'width' => 12,
            'height' => 4,
            'construction_choice' => 'sandwich',
        ]);
        $cells = $this->cells((new ConstructionOfferPages)->fill($quote));

        $this->assertSame('تسمين', $this->valueBeside($cells, 'نوع العنبر'));
        $this->assertSame('81 متر', $this->valueBeside($cells, 'طول العنبر'));
        $this->assertSame('12 متر', $this->valueBeside($cells, 'عرض العنبر'));
        $this->assertSame('4 متر', $this->valueBeside($cells, 'ارتفاع العنبر'));
        $this->assertStringContainsString('972', $this->row($cells, 'اعمال الخرسانة'));
        $this->assertStringContainsString('1,166,400', $this->row($cells, 'اعمال الخرسانة'));
        $this->assertStringContainsString('2,381,400', $this->row($cells, 'المبنى الاستيل'));
        $this->assertStringContainsString('648', $this->row($cells, 'الحوائط عبارة'));
        $this->assertStringContainsString('777,600', $this->row($cells, 'الحوائط عبارة'));
        $this->assertStringContainsString('4,805,400', $this->row($cells, 'TOTAL'));
    }

    public function test_construction_pdf_keeps_the_brochure_when_word_conversion_fails(): void
    {
        $this->app->instance(DocxToPdf::class, new class extends DocxToPdf
        {
            public function convert(string $docxPath): string
            {
                throw new RuntimeException('LibreOffice غير متاح على السيرفر.');
            }
        });

        $quote = new PoultryQuotation([
            'project_type' => 'broiler',
            'length' => 81,
            'width' => 12,
            'height' => 4,
            'construction_choice' => 'sandwich',
        ]);

        $bytes = (new ConstructionOfferPages)->pdfBytes($quote);

        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertGreaterThan(500000, strlen($bytes));
        $this->assertSame(5, preg_match_all('/\/Type\s*\/Page(?![s])/', $bytes));
    }

    private function quote(string $type, float $length, float $width, float $height, int $sideFans): PoultryQuotation
    {
        $quote = PoultryQuotation::create([
            'client_name' => 'عميل تهوية',
            'project_type' => $type,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 10,
            'bird_count' => 1000,
            'bird_weight_kg' => 2.1,
            'back_fans_count' => 0,
            'cooling_units' => 0,
            'windows_count' => 0,
            'side_fans_count' => 0,
            'heaters_count' => 0,
            'barns_count' => 1,
            'include_ventilation' => true,
            'ventilation_side_fans' => $sideFans,
        ]);

        $quote->forceFill([
            'bird_count' => 80952,
            'include_ventilation' => true,
            'ventilation_side_fans' => $sideFans,
        ])->saveQuietly();

        return $quote->fresh();
    }

    /** @return list<list<string>> */
    private function cells(string $docx): array
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($docx));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($docx);
        $this->assertNotFalse($xml);

        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $rows = [];
        foreach ($xpath->query('//w:tr') as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }
            $line = [];
            foreach ($row->childNodes as $child) {
                if (! $child instanceof DOMElement || $child->localName !== 'tc') {
                    continue;
                }
                $text = '';
                foreach ($xpath->query('.//w:t', $child) as $node) {
                    $text .= $node->textContent;
                }
                $line[] = trim($text);
            }
            if ($line !== []) {
                $rows[] = $line;
            }
        }

        return $rows;
    }

    /** @param  list<list<string>>  $rows */
    private function valueBeside(array $rows, string $label): string
    {
        foreach ($rows as $row) {
            if (($row[0] ?? '') === $label) {
                return $row[1] ?? '';
            }
        }

        $this->fail('صف غير موجود: '.$label);
    }

    /** @param  list<list<string>>  $rows */
    private function row(array $rows, string $marker): string
    {
        foreach ($rows as $row) {
            $text = implode(' | ', $row);
            if (str_contains($text, $marker)) {
                return $text;
            }
        }

        $this->fail('صف غير موجود: '.$marker);
    }
}
