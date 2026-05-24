<?php

namespace Tests\Unit;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Services\Poultry\PoultryConfigLoader;
use App\Services\PoultryHousePricingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PoultryHousePricingServiceTest extends TestCase
{
    protected function defaultParams(): array
    {
        return array_merge((new PoultryConfigLoader)->defaultTechnicalConfig(), [
            'concrete_cost_per_m2' => 1200,
            'steel_cost_per_m2' => 2520,
            'wall_cost_per_m2' => 1200,
            'tanks_fixed_cost' => 25000,
            'price_per_bird' => 95,
            'include_tanks' => false,
            'cooling_unit_price' => 1800,
            'window_unit_price' => 450,
            'back_fan_unit_price' => 12500,
            'side_fan_unit_price' => 8500,
            'heater_unit_price' => 9500,
            'control_fixed_cost' => 75000,
            'electricity_fixed_cost' => 0,
        ]);
    }

    protected function broilerInput(array $overrides = []): array
    {
        return array_merge([
            'project_type' => PoultryProjectType::Broiler->value,
            'pricing_scope' => PoultryPricingScope::FullProject->value,
            'hall_length' => 108,
            'hall_width' => 15,
            'hall_height' => 3.5,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 5,
            'bird_weight_kg' => 2.100,
            'birds_per_nest' => 16,
            'side_fans_count' => 6,
            'heaters_count' => 2,
        ], $overrides);
    }

    /** @test */
    public function it_computes_broiler_golden_quantities(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute($this->broilerInput(), $this->defaultParams());

        $this->assertEquals(98, $result['computed']['effective_length']);
        $this->assertEquals(784, $result['computed']['nests_per_line']);
        $this->assertEquals(3920, $result['computed']['total_nests']);
        $this->assertEquals(62720, $result['computed']['bird_count']);
    }

    /** @test */
    public function broiler_81m_has_39_air_windows(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute($this->broilerInput(['hall_length' => 81]), $this->defaultParams());
        $this->assertEquals(39, $result['computed']['windows_count']);
    }

    /** @test */
    public function broiler_108m_has_correct_air_windows_via_technical(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute($this->broilerInput(['hall_length' => 102]), $this->defaultParams());
        $this->assertEquals(49, $result['computed']['windows_count']);
    }

    /** @test */
    public function layer_scope_excludes_side_fans_and_heaters_from_items(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute([
            'project_type' => PoultryProjectType::Layer->value,
            'pricing_scope' => PoultryPricingScope::FullProject->value,
            'hall_length' => 81,
            'hall_width' => 12,
            'hall_height' => 3.5,
            'service_length' => 9,
            'tiers' => 4,
            'lines' => 4,
        ], $this->defaultParams());

        $keys = array_column($result['items'], 'key');
        $this->assertNotContains('side_fans', $keys);
        $this->assertNotContains('heaters', $keys);
        $this->assertEquals(38400, $result['computed']['bird_count']);
        $this->assertEquals(14, $result['computed']['back_fans_count']);
        $this->assertEquals(77.0, $result['computed']['cooling_units']);
    }

    /** @test */
    public function batteries_only_scope_excludes_construction_and_accessories(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute(
            $this->broilerInput(['pricing_scope' => PoultryPricingScope::BatteriesOnly->value]),
            $this->defaultParams()
        );

        $sections = array_column($result['items'], 'section');
        $this->assertEquals(['cages'], array_values(array_unique($sections)));
    }

    /** @test */
    public function construction_only_excludes_batteries_and_mechanical(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute(
            $this->broilerInput(['pricing_scope' => PoultryPricingScope::ConstructionOnly->value]),
            $this->defaultParams()
        );

        $sections = array_column($result['items'], 'section');
        $this->assertNotContains('cages', $sections);
        $this->assertNotContains('ventilation', $sections);
        $this->assertContains('civil', $sections);
    }

    /** @test */
    public function full_project_includes_battery_systems_and_construction(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute($this->broilerInput(), $this->defaultParams());
        $sections = array_column($result['items'], 'section');

        $this->assertContains('civil', $sections);
        $this->assertContains('cages', $sections);
        $this->assertContains('ventilation', $sections);
    }

    /** @test */
    public function wall_price_changes_with_wall_type(): void
    {
        $svc = new PoultryHousePricingService;
        $paramsSandwich = array_merge($this->defaultParams(), ['wall_cost_per_m2' => 1200, 'wall_type' => 'sandwich']);
        $paramsCement = array_merge($this->defaultParams(), ['wall_cost_per_m2' => 2000, 'wall_type' => 'cement']);

        $sandwich = $svc->compute($this->broilerInput(['wall_type' => 'sandwich']), $paramsSandwich);
        $cement = $svc->compute($this->broilerInput(['wall_type' => 'cement']), $paramsCement);

        $wallSandwich = collect($sandwich['items'])->firstWhere('key', 'walls');
        $wallCement = collect($cement['items'])->firstWhere('key', 'walls');

        $this->assertGreaterThan($wallSandwich['total_price'], $wallCement['total_price']);
    }

    /** @test */
    public function quote_subtotal_equals_sum_of_line_items(): void
    {
        $svc = new PoultryHousePricingService;
        $result = $svc->compute($this->broilerInput(), $this->defaultParams());
        $expected = array_sum(array_column($result['items'], 'total_price'));
        $this->assertEquals($expected, $result['subtotal']);
    }

    /** @test */
    public function it_throws_when_length_less_than_service(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $svc = new PoultryHousePricingService;
        $svc->compute($this->broilerInput(['hall_length' => 5, 'service_length' => 10]), $this->defaultParams());
    }

    /** @test */
    public function tanks_fixed_lot_at_configured_price(): void
    {
        $svc = new PoultryHousePricingService;
        $params = array_merge($this->defaultParams(), [
            'include_tanks' => true,
            'tanks_fixed_cost' => 400000,
        ]);
        $result = $svc->compute($this->broilerInput(), $params);
        $tanks = collect($result['items'])->firstWhere('key', 'tanks');
        $this->assertNotNull($tanks);
        $this->assertEquals(1, $tanks['qty']);
        $this->assertEquals(400000, $tanks['unit_price']);
        $this->assertEquals(400000, $tanks['total_price']);
    }

    /** @test */
    public function tanks_excluded_when_disabled_in_settings(): void
    {
        $svc = new PoultryHousePricingService;
        $params = array_merge($this->defaultParams(), [
            'include_tanks' => false,
            'tanks_fixed_cost' => 400000,
        ]);
        $result = $svc->compute($this->broilerInput(), $params);
        $this->assertNull(collect($result['items'])->firstWhere('key', 'tanks'));
    }

    /** @test */
    public function construction_only_includes_tanks_when_enabled(): void
    {
        $svc = new PoultryHousePricingService;
        $params = array_merge($this->defaultParams(), [
            'include_tanks' => true,
            'tanks_fixed_cost' => 400000,
        ]);
        $result = $svc->compute(
            $this->broilerInput(['pricing_scope' => PoultryPricingScope::ConstructionOnly->value]),
            $params
        );
        $tanks = collect($result['items'])->firstWhere('key', 'tanks');
        $this->assertNotNull($tanks);
        $this->assertEquals(400000, $tanks['total_price']);
    }
}
