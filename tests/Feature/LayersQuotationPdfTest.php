<?php

namespace Tests\Feature;

use App\Models\Lookup;
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
            'width' => 16,
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
            'silo_capacity_id' => $this->lookup('silo_capacity', '25', '25 طن', '25'),
            'outer_belt_length_id' => $this->lookup('outer_belt_length', '8', '8 متر', '8'),
            'motor_power_id' => $this->lookup('motor_power', '1_hp', '1 حصان', '1'),
            'manure_motor_count_id' => $this->lookup('manure_motor_count', '4', '4 ماتور', '4'),
            'belts_per_line_id' => $this->lookup('belts_per_line', '3', '3 سيور', '3'),
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

        $this->assertSame([
            'client' => true,
            'location' => true,
            'width' => true,
            'silo' => true,
            'inner' => true,
            'power' => true,
            'old_silo' => false,
            'old_inner' => false,
            'old_power' => false,
            'motors' => true,
            'belts' => true,
            'egg_page' => false,
            'saved_inner' => '20 متر',
            'saved_silo' => '11 طن',
            'saved_power' => '1 حصان',
            'saved_motors' => '4 ماتور',
            'saved_belts' => '3 سيور',
        ], [
            'client' => str_contains($xml, 'عميل الصفحات'),
            'location' => str_contains($xml, 'القاهرة'),
            'width' => ! str_contains($xml, '11,5'),
            'silo' => str_contains($xml, '11 طن'),
            'inner' => str_contains($xml, '20 متر'),
            'power' => str_contains($xml, '1 حصان'),
            'old_silo' => str_contains($xml, '25 طن'),
            'old_inner' => str_contains($xml, '12 متر'),
            'old_power' => str_contains($xml, '1.5 حصان'),
            'motors' => str_contains(preg_replace('/\s+/u', '', strip_tags($xml)) ?? '', 'عدد4ماتور'),
            'belts' => str_contains(preg_replace('/\s+/u', '', strip_tags($xml)) ?? '', '3سيور'),
            'egg_page' => str_contains($xml, 'دولاب البيض'),
            'saved_inner' => $quote->fresh()->innerBeltLength?->label_ar,
            'saved_silo' => $quote->fresh()->siloCapacity?->label_ar,
            'saved_power' => $quote->fresh()->motorPower?->label_ar,
            'saved_motors' => $quote->fresh()->manureMotorCount?->label_ar,
            'saved_belts' => $quote->fresh()->beltsPerLine?->label_ar,
        ]);
        $this->assertStringNotContainsString('6,789,120', $xml);
        $this->assertStringContainsString('دليل تطهير عنابر بطاريات الدواجن الأوتوماتيك', $xml);
        $this->assertStringContainsString('دمياط', $xml);

        $bytes = $template->pdfBytes($quote->fresh());
        $this->assertStringStartsWith('%PDF', $bytes);
    }

    private function lookup(string $type, string $code, string $label, string $value): int
    {
        return (int) Lookup::query()->updateOrCreate(
            ['type' => $type, 'code' => $code],
            [
                'label_ar' => $label,
                'value' => $value,
                'is_active' => true,
            ],
        )->id;
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
