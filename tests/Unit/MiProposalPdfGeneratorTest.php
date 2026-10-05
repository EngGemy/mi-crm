<?php

namespace Tests\Unit;

use App\Models\PoultryQuotation;
use App\Services\Poultry\MiProposalPdfGenerator;
use App\Services\Poultry\ProposalPage2Data;
use App\Services\Poultry\ProposalPage9Data;
use App\Services\Poultry\ProposalPage10Data;
use RuntimeException;
use Tests\TestCase;

class MiProposalPdfGeneratorTest extends TestCase
{
    public function test_page9_and_page10_data_are_dynamic(): void
    {
        // In-memory model only — never persist (avoids touching the live DB).
        $q = new PoultryQuotation([
            'client_name' => 'م/ محمد جامع',
            'client_phone' => '+201000000000',
            'project_type' => 'broiler',
            'pricing_scope' => 'batteries_only',
            'length' => 72,
            'width' => 12,
            'height' => 3.7,
            'tiers' => 3,
            'lines' => 4,
            'barns_count' => 1,
            'bird_weight_kg' => 2.100,
            'bird_price' => 95,
            'exchange_rate' => 99,
            'bird_count' => 23808,
            'total_nests' => 1488,
            'nests_per_line' => 372,
            'battery_cost' => 3666432,
            'subtotal' => 3666432,
            'vat_amount' => 0,
            'vat_percentage' => 0,
            'total' => 3666432,
            'status' => 'draft',
            'quote_number' => 'Q-2026-9999',
            'pricing_snapshot' => [
                'technical' => [
                    'effective_length' => 62,
                    'tiers' => 3,
                    'lines' => 4,
                    'bird_weight_kg' => 2.100,
                    'total_nests' => 1488,
                ],
                'computed' => [
                    'effective_length' => 62,
                    'total_nests' => 1488,
                    'nests_per_line' => 372,
                    'bird_count' => 23808,
                ],
                'financial' => [
                    'subtotal' => '3666432.00',
                    'vat_amount' => '0.00',
                    'total' => '3666432.00',
                ],
                'currency' => ['rate' => 52],
                'parameters' => ['egp_to_usd_rate' => 40],
                'project_type' => 'broiler',
            ],
        ]);
        $q->issued_at = '2026-10-05';
        $q->created_at = '2026-01-01';

        $gen = new MiProposalPdfGenerator;
        $p9 = (new \ReflectionClass($gen))->getMethod('page9Data');
        $p9->setAccessible(true);
        $data9 = $p9->invoke($gen, $q);

        $battery = $data9['sections'][0];
        $this->assertSame('battery', $battery['kind']);
        $this->assertSame('62 متر', $battery['rows'][0]['value']);
        $this->assertSame('3 أدوار', $battery['rows'][1]['value']);
        $this->assertSame('4 خطوط', $battery['rows'][2]['value']);
        $this->assertSame('496 قفص', $battery['rows'][5]['value']);
        $this->assertSame('1,488 قفص', $battery['rows'][6]['value']);

        $selected = collect($data9['sections'])->firstWhere('highlight', true);
        $this->assertNotNull($selected);
        $this->assertSame('care', $selected['kind']);
        $this->assertStringContainsString('2.100', $selected['title']);
        $this->assertSame('16 طائر', $selected['rows'][0]['value']);
        $this->assertSame('23,808 طائر', $selected['rows'][1]['value']);
        $this->assertSame('406 سم²', $selected['rows'][2]['value']);
        $this->assertSame('6.25 سم', $selected['rows'][3]['value']);

        $html = view('poultry.proposal.page9', $data9)->render();
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringContainsString('المواصفات الفنية للبطاريات', $html);
        $this->assertStringContainsString('1,488 قفص', $html);
        $this->assertStringContainsString('الوزن المختار', $html);
        $this->assertSame(1, substr_count($html, 'الوزن المختار'));

        $p10 = (new \ReflectionClass($gen))->getMethod('page10Data');
        $p10->setAccessible(true);
        $data10 = $p10->invoke($gen, $q);

        $this->assertSame('م/ محمد جامع', $data10['clientName']);
        $this->assertSame($q->quote_number, $data10['customerId']);
        $this->assertSame('05/10/2026', $data10['date']);
        $this->assertSame('08/10/2026', $data10['validityDate']);
        $this->assertGreaterThan(1, $data10['exchangeRate']);
        $this->assertSame(52.0, $data10['exchangeRate']);
        $this->assertSame('EGP per USD', $data10['rateUnit']);
        $this->assertEqualsWithDelta(3666432 / 52, $data10['totalUsd'], 1e-6);
        $this->assertSame(3666432.0, $data10['totalEgp']);
        $this->assertSame('غير خاضع', $data10['taxed']);
        $this->assertSame('داجن', $data10['unit']);
        $offer = collect($data10['sections'])->firstWhere('kind', 'offer');
        $this->assertStringContainsString('تسمين', $offer['description']);

        $html = view('poultry.proposal.page10', $data10)->render();
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringContainsString('Subtotal', $html);
        $this->assertStringContainsString('VAT', $html);
        $this->assertStringContainsString('غير خاضع', $html);
        $this->assertStringContainsString('ساري حتى 08/10/2026', $html);
    }

    public function test_page10_rate_is_egp_per_usd_and_barn_amounts_reconcile(): void
    {
        $total = 100.0;
        $subtotal = 87.72;
        $vat = 12.28;
        $rate = 52.5;
        $barns = 3;

        $data = (new ProposalPage10Data)->from($this->page10Quote([
            'barns_count' => $barns,
            'exchange_rate' => 99,
            'pricing_snapshot' => [
                'project_type' => 'broiler',
                'financial' => [
                    'subtotal' => $subtotal,
                    'vat_amount' => $vat,
                    'total' => $total,
                ],
                'currency' => ['rate' => $rate],
                'parameters' => ['egp_to_usd_rate' => 40],
            ],
        ]));

        $this->assertGreaterThan(1, $data['exchangeRate']);
        $this->assertSame('EGP per USD', $data['rateUnit']);
        $this->assertSame($rate, $data['exchangeRate']);
        $this->assertSame('14% شامل', $data['taxed']);
        $this->assertEqualsWithDelta($total / $rate, $data['totalUsd'], 1e-9);
        $this->assertEqualsWithDelta($subtotal / $rate, $data['subtotalUsd'], 1e-9);
        $this->assertEqualsWithDelta($vat / $rate, $data['vatUsd'], 1e-9);
        $this->assertEqualsWithDelta($total / $barns, $data['perBarnEgp'], 1e-9);
        $this->assertEqualsWithDelta($data['perBarnEgp'] / $rate, $data['perBarnUsd'], 1e-9);
        $this->assertEqualsWithDelta($data['perBarnEgp'] * $barns, $data['totalEgp'], 0.01);
        $this->assertEqualsWithDelta($data['perBarnUsd'] * $barns, $data['totalUsd'], 0.01);
        $this->assertNotEquals(round($data['perBarnEgp'], 2), $data['perBarnEgp']);

        $rows = collect($data['sections'])->firstWhere('kind', 'offer')['rows'];
        $this->assertSame(['subtotal', 'vat', 'total'], array_column($rows, 'key'));
    }

    public function test_page10_rate_falls_through_to_snapshot_parameters_only(): void
    {
        $data = (new ProposalPage10Data)->from($this->page10Quote([
            'exchange_rate' => 80,
            'pricing_snapshot' => [
                'financial' => [
                    'subtotal' => '100.00',
                    'vat_amount' => '0.00',
                    'total' => '100.00',
                ],
                'parameters' => ['egp_to_usd_rate' => 47.25],
            ],
        ]));

        $this->assertGreaterThan(1, $data['exchangeRate']);
        $this->assertSame(47.25, $data['exchangeRate']);
    }

    public function test_page10_rejects_a_rate_that_is_not_egp_per_usd(): void
    {
        $this->expectException(RuntimeException::class);

        (new ProposalPage10Data)->from($this->page10Quote([
            'pricing_snapshot' => [
                'financial' => [
                    'subtotal' => '100.00',
                    'vat_amount' => '0.00',
                    'total' => '100.00',
                ],
                'currency' => ['rate' => 0.02],
                'parameters' => ['egp_to_usd_rate' => 48],
            ],
        ]));
    }

    public function test_page10_blocks_when_financial_total_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('financial.total');

        (new ProposalPage10Data)->from($this->page10Quote([
            'pricing_snapshot' => [
                'financial' => [
                    'subtotal' => '3666432.00',
                    'vat_amount' => '0.00',
                ],
                'currency' => ['rate' => 52],
            ],
        ]));
    }

    private function page10Quote(array $overrides): PoultryQuotation
    {
        $q = new PoultryQuotation(array_merge([
            'client_name' => 'م/ محمد جامع',
            'project_type' => 'broiler',
            'barns_count' => 1,
            'quote_number' => 'Q-2026-9999',
        ], $overrides));
        $q->issued_at = '2026-10-05';
        $q->created_at = '2026-01-01';

        return $q;
    }

    public function test_page9_care_rows_follow_the_snapshot_not_live_settings(): void
    {
        $q = new PoultryQuotation([
            'project_type' => 'broiler',
            'tiers' => 3,
            'lines' => 4,
            'bird_weight_kg' => 2.1,
            'bird_count' => 1600,
            'total_nests' => 100,
            'pricing_snapshot' => [
                'technical' => [
                    'effective_length' => 62,
                    'tiers' => 3,
                    'lines' => 4,
                    'bird_weight_kg' => 2.100,
                    'birds_per_nest' => 16,
                    'total_nests' => 100,
                ],
                'computed' => [
                    'total_nests' => 100,
                    'bird_count' => 1600,
                ],
                'parameters' => [
                    'broiler_weight_birds_map' => [
                        '1.000' => 20,
                        '2.100' => 10,
                    ],
                ],
            ],
        ]);

        $data = (new ProposalPage9Data)->from($q);
        $care = collect($data['sections'])->where('kind', 'care')->values();

        $this->assertCount(2, $care);
        $this->assertFalse($care[0]['highlight']);
        $this->assertSame('20 طائر', $care[0]['rows'][0]['value']);
        $this->assertSame('2,000 طائر', $care[0]['rows'][1]['value']);

        $this->assertTrue($care[1]['highlight']);
        $this->assertSame('16 طائر', $care[1]['rows'][0]['value']);
        $this->assertSame('1,600 طائر', $care[1]['rows'][1]['value']);
    }

    public function test_page9_layer_quote_uses_a_single_snapshot_care_block(): void
    {
        $q = new PoultryQuotation([
            'project_type' => 'layer',
            'tiers' => 4,
            'lines' => 5,
            'birds_per_nest' => 10,
            'pricing_snapshot' => [
                'project_type' => 'layer',
                'technical' => [
                    'effective_length' => 60,
                    'tiers' => 4,
                    'lines' => 5,
                    'birds_per_nest' => 10,
                    'layer_max_bird_weight_kg' => 1.7,
                ],
                'computed' => [
                    'total_nests' => 4000,
                    'bird_count' => 40000,
                ],
            ],
        ]);

        $data = (new ProposalPage9Data)->from($q);
        $care = collect($data['sections'])->where('kind', 'care')->values();

        $this->assertCount(1, $care);
        $this->assertTrue($care[0]['highlight']);
        $this->assertStringContainsString('1.700', $care[0]['title']);
        $this->assertSame('10 طائر', $care[0]['rows'][0]['value']);
        $this->assertSame('40,000 طائر', $care[0]['rows'][1]['value']);
        $this->assertSame('4,000 قفص', $data['sections'][0]['rows'][6]['value']);
    }

    public function test_page2_barn_card_comes_from_the_snapshot(): void
    {
        $q = new PoultryQuotation([
            'client_name' => 'م/ محمد جامع',
            'client_location' => 'كفر شيخ',
            'client_address' => 'عنوان آخر',
            'project_type' => 'layer',
            'length' => 99,
            'width' => 99,
            'height' => 9,
            'pricing_snapshot' => [
                'project_type' => 'broiler',
                'inputs' => [
                    'hall_length' => 71,
                    'hall_width' => 13,
                    'hall_height' => 2.9,
                    'project_type' => 'layer',
                ],
            ],
        ]);

        $data = (new ProposalPage2Data)->from($q);
        $rows = collect($data['sections'])->firstWhere('kind', 'barn')['rows'];

        $this->assertSame('تسمين', $data['projectType']);
        $this->assertSame('مقدم الى م/ محمد جامع', $data['sections'][1]['text']);
        $this->assertSame('تسمين', $rows[0]['value']);
        $this->assertSame('71 متر', $rows[1]['value']);
        $this->assertSame('13 متر', $rows[2]['value']);
        $this->assertSame('2.9 متر', $rows[3]['value']);
        $this->assertSame('كفر شيخ', $rows[4]['value']);

        $html = view('poultry.proposal.page2', $data)->render();
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringContainsString('71 متر', $html);
        $this->assertStringContainsString('كفر شيخ', $html);
        $this->assertStringNotContainsString('99 متر', $html);
    }
}
