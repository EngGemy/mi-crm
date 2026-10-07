<?php

namespace Tests\Unit;

use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Quotations\Exceptions\LayerRearingDisabledException;
use App\Quotations\QuotationTemplateResolver;
use App\Quotations\Templates\BroilerQuotationTemplate;
use App\Quotations\Templates\LayersQuotationTemplate;
use App\Services\Poultry\MiProposalPdfGenerator;
use App\Services\Poultry\ProposalPage2Data;
use App\Services\Poultry\ProposalPage6Data;
use App\Services\Poultry\ProposalPage9Data;
use App\Services\Poultry\ProposalPage10Data;
use Tests\TestCase;

class QuotationTemplateResolverTest extends TestCase
{
    public function test_broiler_project_types_resolve_to_the_broiler_template(): void
    {
        $resolver = $this->resolver();

        $this->assertInstanceOf(BroilerQuotationTemplate::class, $resolver->resolve($this->quote('broiler')));
        $this->assertInstanceOf(BroilerQuotationTemplate::class, $resolver->resolve($this->quote('broiler_auto_exit')));
        $this->assertInstanceOf(BroilerQuotationTemplate::class, $resolver->resolve($this->quote(null)));
    }

    public function test_layer_project_types_resolve_to_the_layers_template(): void
    {
        $resolver = $this->resolver();

        $this->assertInstanceOf(LayersQuotationTemplate::class, $resolver->resolve($this->quote('layer')));
        $this->assertInstanceOf(LayersQuotationTemplate::class, $resolver->resolve($this->quote('layer_auto_collect')));
    }

    public function test_layer_rearing_is_blocked_until_the_flag_is_enabled(): void
    {
        config(['quotations.layer_rearing_enabled' => false]);

        $this->expectException(LayerRearingDisabledException::class);
        $this->resolver()->resolve($this->quote('layer_rearing'));
    }

    public function test_layer_rearing_resolves_to_layers_when_the_flag_is_enabled(): void
    {
        config(['quotations.layer_rearing_enabled' => true]);

        $template = $this->resolver()->resolve($this->quote('layer_rearing'));

        $this->assertInstanceOf(LayersQuotationTemplate::class, $template);
        $this->assertTrue($template->supports('layer_rearing'));
    }

    public function test_saved_quote_type_wins_over_project_type(): void
    {
        $lookup = new Lookup([
            'type' => Lookup::TYPE_QUOTE_TYPE,
            'code' => 'batteries_broiler_eg',
        ]);
        $lookup->id = 11;

        $quote = $this->quote('layer');
        $quote->quote_type_id = 11;
        $quote->setRelation('quoteType', $lookup);

        $this->assertInstanceOf(BroilerQuotationTemplate::class, $this->resolver()->resolve($quote));
    }

    public function test_old_quote_without_quote_type_falls_back_to_project_type(): void
    {
        $layer = $this->quote('layer');
        $layer->quote_type_id = null;

        $broiler = $this->quote('broiler');
        $broiler->quote_type_id = null;

        $resolver = $this->resolver();

        $this->assertInstanceOf(LayersQuotationTemplate::class, $resolver->resolve($layer));
        $this->assertInstanceOf(BroilerQuotationTemplate::class, $resolver->resolve($broiler));
    }

    public function test_broiler_pdf_data_matches_the_generator_inputs(): void
    {
        $quote = $this->broilerFixture();
        $template = new BroilerQuotationTemplate;
        $data = $template->buildData($quote);

        $direct = [
            'page2' => (new ProposalPage2Data)->from($quote),
            'page6' => (new ProposalPage6Data)->from($quote),
            'page9' => (new ProposalPage9Data)->from($quote),
            'page10' => (new ProposalPage10Data)->from($quote),
        ];

        $this->assertEquals($direct, $data);
        $this->assertSame($this->brochureHtml($direct), $this->brochureHtml($data));

        $response = response('pdf-bytes', 200, ['Content-Type' => 'application/pdf']);
        $this->mock(MiProposalPdfGenerator::class, function ($mock) use ($quote, $response) {
            $mock->shouldReceive('download')->once()->with($quote)->andReturn($response);
        });

        $this->assertSame($response, $template->render($quote));
        $this->assertSame('broiler', $template->shareCardData($quote)['template']);
    }

    public function test_layers_build_data_keeps_spare_parts_out_of_the_total_and_balances_payments(): void
    {
        $quote = new PoultryQuotation([
            'project_type' => 'layer',
            'client_name' => 'م/ محمد جامع',
            'quote_number' => 'Q-2026-0001',
            'total' => 0.10,
            'subtotal' => 0.09,
            'vat_amount' => 0.01,
            'exchange_rate' => 3,
            'barns_count' => 1,
            'birds_per_nest' => 10,
        ]);

        $data = (new LayersQuotationTemplate)->buildData($quote);

        $this->assertSame(1.0, $data['spare_parts']['percent']);
        $this->assertTrue($data['spare_parts']['descriptive_only']);
        $this->assertEqualsWithDelta(0.10, $data['financial']['total'], 0.001);
        $this->assertNull($data['stocking']['area_cm2']);
        $this->assertNull($data['stocking']['feeding_cm']);
        $this->assertSame(
            ['subtotal', 'vat_amount', 'total', 'barn_egp', 'barn_usd'],
            array_keys($data['financial'])
        );

        $this->assertSame([7, 3, 0], $this->cents($data['payments'], 'egp'));
        $this->assertSame([2, 1, 0], $this->cents($data['payments'], 'usd'));
        $this->assertSame('layers', (new LayersQuotationTemplate)->shareCardData($quote)['template']);
    }

    public function test_layers_original_pages_keep_static_text_and_swap_dynamic_values(): void
    {
        $quote = $this->quote('layer');
        $quote->client_name = 'عميل الصفحات';
        $quote->length = 99;

        $path = (new \App\Quotations\Layers\LayersOriginalPages)->fill($quote);
        $xml = $this->docxText($path);
        @unlink($path);

        $this->assertStringContainsString('عميل الصفحات', $xml);
        $this->assertStringNotContainsString('م/ محمد جامع', $xml);
        $this->assertStringContainsString('99', $xml);
        $this->assertStringContainsString('دليل تطهير عنابر بطاريات الدواجن الأوتوماتيك', $xml);
    }

    public function test_resolver_is_registered_as_a_singleton(): void
    {
        $this->assertSame(
            app(QuotationTemplateResolver::class),
            app(QuotationTemplateResolver::class)
        );
    }

    public function test_layers_image_map_leaves_out_image22(): void
    {
        $images = collect(config('quotations.layers'))->flatten();

        $this->assertTrue($images->contains('image1.jpg'));
        $this->assertFalse($images->contains('image22.png'));
    }

    private function resolver(): QuotationTemplateResolver
    {
        return new QuotationTemplateResolver(
            new BroilerQuotationTemplate,
            new LayersQuotationTemplate,
        );
    }

    private function quote(?string $projectType): PoultryQuotation
    {
        return new PoultryQuotation([
            'project_type' => $projectType,
        ]);
    }

    private function broilerFixture(): PoultryQuotation
    {
        $quote = new PoultryQuotation([
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
        $quote->issued_at = '2026-10-05';
        $quote->created_at = '2026-01-01';

        return $quote;
    }

    /** @param  array<string, array<string, mixed>>  $data */
    private function brochureHtml(array $data): string
    {
        return view('poultry.proposal.page2', $data['page2'])->render()
            ."\n".view('poultry.proposal.page6-motors', $data['page6'])->render()
            ."\n".view('poultry.proposal.page6-belts', $data['page6'])->render()
            ."\n".view('poultry.proposal.page9', $data['page9'])->render()
            ."\n".view('poultry.proposal.page10', $data['page10'])->render();
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function docxText(string $path): string
    {
        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return is_string($xml) ? $xml : '';
    }

    private function cents(array $lines, string $key): array
    {
        return array_map(
            fn (array $line) => (int) round(((float) $line[$key]) * 100),
            $lines
        );
    }
}
