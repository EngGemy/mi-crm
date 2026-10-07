<?php

namespace Tests\Feature;

use App\Models\PoultryQuotation;
use App\Quotations\Templates\BroilerQuotationTemplate;
use App\Quotations\Templates\LayersQuotationTemplate;
use App\Services\Poultry\ProposalPage10Data;
use App\Services\Poultry\ProposalPage2Data;
use App\Services\Poultry\ProposalPage6Data;
use App\Services\Poultry\ProposalPage9Data;
use App\Quotations\Layers\LayersOriginalPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayersQuotationPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_layers_pdf_renders_within_limits_and_payments_match_the_total(): void
    {
        $quote = PoultryQuotation::create([
            'client_name' => 'عميل الصفحات',
            'client_location' => 'القاهرة',
            'project_type' => 'layer',
            'length' => 90,
            'width' => 12.5,
            'height' => 4,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 10,
            'bird_count' => 38400,
            'total_nests' => 3840,
            'nests_per_line' => 960,
            'back_fans_count' => 0,
            'cooling_units' => 0,
            'windows_count' => 0,
            'side_fans_count' => 0,
            'heaters_count' => 0,
            'exchange_rate' => 52,
            'barns_count' => 1,
            'pricing_snapshot' => [
                'technical' => [
                    'effective_length' => 72,
                    'tiers' => 4,
                    'lines' => 4,
                    'nests_one_side' => 120,
                    'total_nests' => 3840,
                    'total_birds' => 38400,
                    'birds_per_nest' => 10,
                    'layer_nest_module_m' => 0.60,
                ],
                'computed' => [
                    'bird_count' => 38400,
                    'total_nests' => 3840,
                    'nests_per_line' => 960,
                    'effective_length' => 72,
                ],
                'financial' => [
                    'subtotal' => '100.00',
                    'vat_amount' => '14.00',
                    'total' => '114.00',
                ],
            ],
        ]);
        $quote->issued_at = $quote->issued_at ?: now();

        $template = new LayersQuotationTemplate;
        $data = $template->buildData($quote->fresh());
        $egpCents = 0;
        foreach ($data['payments'] as $line) {
            $egpCents += (int) round(((float) $line['egp']) * 100);
        }

        $this->assertSame(11400, $egpCents);
        $this->assertNull($data['stocking']['area_cm2']);
        $this->assertEqualsWithDelta(6.0, $data['stocking']['feeding_cm'], 0.001);

        $path = (new LayersOriginalPages)->fill($quote->fresh());
        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('عميل الصفحات', $xml);
        $this->assertStringContainsString('القاهرة', $xml);
        $this->assertStringContainsString('12,5', $xml);
        $this->assertStringNotContainsString('6,789,120', $xml);
        $this->assertStringContainsString('دليل تطهير عنابر بطاريات الدواجن الأوتوماتيك', $xml);
        $this->assertStringContainsString('دمياط', $xml);
    }

    public function test_broiler_pdf_data_is_unchanged(): void
    {
        $quote = new PoultryQuotation([
            'client_name' => 'م/ محمد جامع',
            'project_type' => 'broiler',
            'pricing_scope' => 'batteries_only',
            'length' => 72,
            'width' => 12,
            'height' => 3.7,
            'tiers' => 3,
            'lines' => 4,
            'barns_count' => 1,
            'exchange_rate' => 52,
            'quote_number' => 'Q-2026-9999',
            'pricing_snapshot' => [
                'technical' => ['effective_length' => 62, 'tiers' => 3, 'lines' => 4, 'total_nests' => 1488],
                'computed' => ['effective_length' => 62, 'total_nests' => 1488, 'nests_per_line' => 372, 'bird_count' => 23808],
                'financial' => ['subtotal' => '3666432.00', 'vat_amount' => '0.00', 'total' => '3666432.00'],
                'currency' => ['rate' => 52],
                'project_type' => 'broiler',
            ],
        ]);
        $quote->issued_at = '2026-10-05';

        $data = (new BroilerQuotationTemplate)->buildData($quote);

        $this->assertEquals([
            'page2' => (new ProposalPage2Data)->from($quote),
            'page6' => (new ProposalPage6Data)->from($quote),
            'page9' => (new ProposalPage9Data)->from($quote),
            'page10' => (new ProposalPage10Data)->from($quote),
        ], $data);
    }

}
