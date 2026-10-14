<?php

namespace Tests\Feature;

use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Quotations\Broiler\BroilerAutoExitPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BroilerAutoExitDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_exit_document_replaces_form_values_and_keeps_the_brochure(): void
    {
        $quote = PoultryQuotation::create([
            'client_name' => 'إيهاب علي',
            'client_location' => 'المنصورة',
            'project_type' => 'broiler_auto_exit',
            'length' => 90,
            'width' => 15,
            'height' => 4.2,
            'tiers' => 3,
            'lines' => 5,
            'birds_per_nest' => 16,
            'bird_count' => 48000,
            'bird_weight_kg' => 2.1,
            'total_nests' => 3000,
            'nests_per_line' => 600,
            'back_fans_count' => 0,
            'cooling_units' => 0,
            'windows_count' => 0,
            'side_fans_count' => 0,
            'heaters_count' => 0,
            'barns_count' => 1,
            'exchange_rate' => 48,
            'issued_at' => '2026-10-13 08:00:00',
            'inner_belt_length_id' => $this->lookup('inner_belt_length', '16', '16 متر', '16'),
            'outer_belt_length_id' => $this->lookup('outer_belt_length', '9', '9 متر', '9'),
            'motor_power_id' => $this->lookup('motor_power', '2', '2 حصان', '2'),
            'silo_capacity_id' => $this->lookup('silo_capacity', '14', '14 طن', '14'),
            'pricing_snapshot' => [
                'project_type' => 'broiler_auto_exit',
                'technical' => [
                    'effective_length' => 80,
                    'tiers' => 4,
                    'lines' => 5,
                    'nests_per_line' => 600,
                    'total_nests' => 3000,
                    'birds_per_nest' => 16,
                    'bird_weight_kg' => 2.1,
                ],
                'computed' => [
                    'effective_length' => 80,
                    'nests_per_line' => 600,
                    'total_nests' => 3000,
                    'bird_count' => 48000,
                ],
                'financial' => [
                    'subtotal' => '960000.00',
                    'vat_amount' => '0.00',
                    'total' => '960000.00',
                ],
            ],
        ]);

        $path = (new BroilerAutoExitPages)->fill($quote->fresh());
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $compact = preg_replace('/\s+/u', '', strip_tags($xml)) ?? '';

        $this->assertStringContainsString('إيهاب علي', $xml);
        $this->assertStringContainsString('المنصورة', $xml);
        $this->assertStringContainsString('تسمين تخريج آلي', $xml);
        $this->assertStringContainsString('90 متر', $xml);
        $this->assertStringContainsString('15 متر', $xml);
        $this->assertStringContainsString('4.2 متر', $xml);
        $this->assertStringContainsString('80 متر', $xml);
        $this->assertStringContainsString('3 أدوار', $xml);
        $this->assertStringContainsString('5 خطوط', $xml);
        $this->assertStringContainsString('600 قفص', $xml);
        $this->assertStringContainsString('3,000 قفص', $xml);
        $this->assertStringContainsString('16 متر', $xml);
        $this->assertStringContainsString('9 متر', $xml);
        $this->assertStringContainsString('14 طن', $xml);
        $this->assertStringContainsString('ايلو سعة 14 طن', $xml);
        $this->assertStringContainsString('13/10/2026', $xml);
        $this->assertStringContainsString('960,000', $xml);
        $this->assertStringContainsString('20,000', $xml);
        $this->assertStringContainsString('2.650 كجم', $xml);
        $this->assertStringContainsString('54,000 طائر', $xml);
        $this->assertStringContainsString('48,000 طائر', $xml);
        $this->assertStringContainsString('39,000 طائر', $xml);
        $this->assertStringContainsString('عدد3ماتور', $compact);
        $this->assertStringContainsString('3سيور', $compact);
        $this->assertStringContainsString('داخلىبطول16متر', $compact);
        $this->assertStringContainsString('خارجىبطول9متر', $compact);
        $this->assertStringContainsString('102 سم', $xml);
        $this->assertStringContainsString('3.60 متر', $xml);
        $this->assertStringNotContainsString('3.73 متر', $xml);
        $this->assertStringContainsString('10متر', $compact);
        $this->assertStringContainsString('منظومة التخريج الالى للقطيع', $xml);
        $this->assertStringContainsString('دليل تطهير عنابر بطاريات الدواجن الأوتوماتيك', $xml);

        $this->assertStringNotContainsString('EHAB', $xml);
        $this->assertStringNotContainsString('1/1/1', $xml);
        $this->assertStringNotContainsString('81 متر', $xml);
        $this->assertStringNotContainsString('12 متر', $xml);
        $this->assertStringNotContainsString('8 متر', $xml);
        $this->assertStringNotContainsString('11 طن', $xml);
        $this->assertStringNotContainsString('72 متر', $xml);
        $this->assertStringNotContainsString('4 أدوار', $xml);
        $this->assertStringNotContainsString('4 خطوط', $xml);
        $this->assertStringNotContainsString('576 قفص', $xml);
        $this->assertStringNotContainsString('2,304 قفص', $xml);
        $this->assertStringNotContainsString('5,197,824', $xml);
        $this->assertStringNotContainsString('2.550', $xml);
        $this->assertStringNotContainsString('41,472', $xml);
        $this->assertStringContainsString('بقوه2حصان', $compact);
        $this->assertStringNotContainsString('1.5حصان', $compact);
        $this->assertStringNotContainsString('عدد4ماتور', $compact);
        $this->assertStringNotContainsString('4ماتور', $compact);
        $this->assertStringNotContainsString('4سيور', $compact);
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
}
