<?php

namespace Tests\Unit;

use App\Models\Lookup;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Support\LayerBaseLookups;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayerBaseLookupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_installs_circled_defaults_and_half_rate(): void
    {
        $this->assertSame('8', $this->valueOf(Lookup::TYPE_LAYER_SERVICE));
        $this->assertSame('10', $this->valueOf(Lookup::TYPE_LAYER_BIRDS));
        $this->assertSame('31', $this->valueOf(Lookup::TYPE_LAYER_SILO_SIDE));
        $this->assertSame('11', $this->valueOf(Lookup::TYPE_LAYER_INNER_BELT));
        $this->assertSame('12', $this->valueOf(Lookup::TYPE_LAYER_WORK_BELT));
        $this->assertSame('4', $this->valueOf(Lookup::TYPE_LAYER_PEDAL_BELTS));
        $this->assertSame('1', $this->valueOf(Lookup::TYPE_LAYER_CAGE_POWER));
        $this->assertSame('50', $this->valueOf(Lookup::TYPE_LAYER_EGG_WHEEL));

        $this->assertSame(15.5, LayerBaseLookups::rateFromBase(31.0));
        $this->assertSame(5.5, LayerBaseLookups::rateFromBase(11.0));
        $this->assertSame(6.0, LayerBaseLookups::rateFromBase(12.0));
        $this->assertSame(2.0, LayerBaseLookups::rateFromBase(4.0));
        $this->assertSame(0.5, LayerBaseLookups::rateFromBase(1.0));
        $this->assertSame(25.0, LayerBaseLookups::rateFromBase(50.0));

        $this->assertNotNull(Lookup::query()->where('type', Lookup::TYPE_LAYER_BIRDS)->where('code', '9')->first());
        $silo = collect(LayerBaseLookups::rateRows(LayerBaseLookups::defaultState()))->firstWhere('label', 'جهة السايلو');
        $this->assertSame(['base' => '31', 'rate' => '15.5'], [
            'base' => $silo['base'],
            'rate' => $silo['rate'],
        ]);
    }

    public function test_seeder_keeps_layer_rows_and_does_not_replace_broiler_belts(): void
    {
        $this->seed(LookupSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->assertSame(1, Lookup::query()->where('type', Lookup::TYPE_LAYER_SILO_SIDE)->where('code', '31')->count());
        $this->assertSame('12', Lookup::query()->where('type', Lookup::TYPE_INNER_BELT_LENGTH)->where('is_active', true)->value('value'));
        $this->assertSame('1', Lookup::query()->whereKey(Lookup::defaultId(Lookup::TYPE_MANURE_MOTOR_COUNT))->value('code'));
    }

    public function test_layer_service_of_eight_and_ten_birds_feed_the_calculator(): void
    {
        $calc = new PoultryTechnicalCalculator;
        $layer = $calc->compute([
            'project_type' => 'layer',
            'barn_length' => 81,
            'barn_width' => 12,
            'service_length' => LayerBaseLookups::defaultNumeric(Lookup::TYPE_LAYER_SERVICE),
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => LayerBaseLookups::defaultNumeric(Lookup::TYPE_LAYER_BIRDS),
        ], []);

        $this->assertSame(10, $layer['birds_per_nest']);
        $this->assertSame(72.0, $layer['effective_length']);
        $this->assertSame($layer['total_nests'] * 10, $layer['total_birds']);

        $broiler = $calc->compute([
            'project_type' => 'broiler',
            'barn_length' => 81,
            'barn_width' => 12,
            'tiers' => 4,
            'lines' => 4,
            'bird_weight_kg' => 2.1,
        ], []);

        $this->assertSame(16, $broiler['birds_per_nest']);
        $this->assertNotSame(8.0, $broiler['barn_length'] - $broiler['effective_length']);
    }

    private function valueOf(string $type): ?string
    {
        $id = Lookup::defaultId($type);

        return $id === null ? null : Lookup::query()->whereKey($id)->value('value');
    }
}
