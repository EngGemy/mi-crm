<?php

namespace Tests\Feature;

use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Quotations\Exceptions\LayerRearingDisabledException;
use App\Quotations\QuotationTemplateResolver;
use App\Quotations\Templates\BroilerQuotationTemplate;
use App\Quotations\Templates\LayersQuotationTemplate;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoultryQuotationQuoteTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_type_seeder_adds_layers_without_disabling_broiler(): void
    {
        $this->seed(LookupSeeder::class);
        $this->seed(LookupSeeder::class);

        $broiler = $this->quoteType('batteries_broiler_eg');
        $layers = $this->quoteType('batteries_layers_eg');

        $this->assertTrue($broiler->is_active);
        $this->assertSame('بطاريات فقط تسمين مصري', $broiler->label_ar);
        $this->assertTrue($layers->is_active);
        $this->assertSame('بطاريات فقط بياض مصري', $layers->label_ar);
        $this->assertSame('batteries_only_layers_eg', $layers->value);
        $this->assertSame(1, Lookup::query()->where('type', Lookup::TYPE_QUOTE_TYPE)->where('code', 'batteries_layers_eg')->count());
    }

    public function test_creating_a_quotation_stores_the_template_quote_type(): void
    {
        $this->seed(LookupSeeder::class);

        $broiler = $this->make('broiler');
        $layer = $this->make('layer');
        $autoCollect = $this->make('layer_auto_collect');

        $this->assertSame($this->quoteType('batteries_broiler_eg')->id, $broiler->quote_type_id);
        $this->assertSame($this->quoteType('batteries_layers_eg')->id, $layer->quote_type_id);
        $this->assertSame($this->quoteType('batteries_layers_eg')->id, $autoCollect->quote_type_id);
    }

    public function test_an_explicit_quote_type_is_not_replaced_on_create(): void
    {
        $this->seed(LookupSeeder::class);

        $broilerId = $this->quoteType('batteries_broiler_eg')->id;
        $frozen = $this->make('layer', ['quote_type_id' => $broilerId]);

        $this->assertSame($broilerId, $frozen->fresh()->quote_type_id);
        $this->assertInstanceOf(
            BroilerQuotationTemplate::class,
            app(QuotationTemplateResolver::class)->resolve($frozen->fresh())
        );
    }

    public function test_disabled_layer_rearing_is_saved_without_a_quote_type_and_resolve_throws(): void
    {
        $this->seed(LookupSeeder::class);
        config(['quotations.layer_rearing_enabled' => false]);

        $quote = $this->make('layer_rearing');

        $this->assertNull($quote->fresh()->quote_type_id);

        config(['quotations.layer_rearing_enabled' => true]);
        $enabled = $this->make('layer_rearing');

        $this->assertSame($this->quoteType('batteries_layers_eg')->id, $enabled->quote_type_id);
        $this->assertInstanceOf(
            LayersQuotationTemplate::class,
            app(QuotationTemplateResolver::class)->resolve($enabled)
        );

        config(['quotations.layer_rearing_enabled' => false]);
        $this->expectException(LayerRearingDisabledException::class);
        app(QuotationTemplateResolver::class)->resolve($quote->fresh());
    }

    private function quoteType(string $code): Lookup
    {
        return Lookup::query()
            ->where('type', Lookup::TYPE_QUOTE_TYPE)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function make(string $projectType, array $extra = []): PoultryQuotation
    {
        return PoultryQuotation::create(array_merge([
            'client_name' => 'عميل اختبار',
            'project_type' => $projectType,
            'length' => 81,
            'width' => 11.5,
            'height' => 3.5,
            'tiers' => 4,
            'lines' => 4,
            'bird_count' => 0,
            'total_nests' => 0,
            'nests_per_line' => 0,
            'back_fans_count' => 0,
            'cooling_units' => 0,
            'windows_count' => 0,
            'side_fans_count' => 0,
            'heaters_count' => 0,
            'pricing_snapshot' => [
                'financial' => [
                    'subtotal' => '100.00',
                    'vat_amount' => '14.00',
                    'total' => '114.00',
                ],
            ],
        ], $extra));
    }
}
