<?php

namespace Tests\Unit;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Services\Poultry\PoultryConfigLoader;
use App\Services\Pricing\DTOs\QuotationInput;
use App\Services\Pricing\PricingCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PricingCalculatorTest extends TestCase
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
            'default_service_length' => 10,
            'fan_capacity_kg' => 5000,
            'cooling_pad_meters_per_fan' => 5.5,
            'layer_nest_module_m' => 0.60,
            'layer_birds_per_nest' => 10,
            'layer_max_bird_weight_kg' => 1.7,
            'broiler_weight_birds_map' => [
                '1.600' => 21, '1.850' => 18, '2.100' => 16, '2.650' => 13, '2.800' => 12,
            ],
            'width_lines_map' => ['12' => 4, '15' => 5],
            'side_fan_rules' => [],
            'heater_rules' => [],
        ]);
    }

    protected function calculator(): PricingCalculator
    {
        return new PricingCalculator($this->defaultParams());
    }

    /** @test */
    public function it_calculates_broiler_bird_count_for_108x15(): void
    {
        $calc = $this->calculator();
        $result = $calc->calculate(new QuotationInput(
            length: 108, width: 15, height: 3.5,
            tiers: 4, lines: 5,
            projectType: PoultryProjectType::Broiler->value,
            serviceLength: 10,
            birdWeightKg: 2.1,
            birdsPerNest: 16,
            sideFansCount: 6,
            heatersCount: 2,
        ));
        $this->assertEquals(62720, $result->birdCount);
        $this->assertEquals(784, $result->nestsPerLine);
    }

    /** @test */
    public function it_calculates_layer_bird_count(): void
    {
        $calc = $this->calculator();
        $result = $calc->calculate(new QuotationInput(
            length: 81, width: 12, height: 3.5,
            tiers: 4, lines: 4,
            projectType: PoultryProjectType::Layer->value,
            serviceLength: 9,
        ));
        $this->assertEquals(38400, $result->birdCount);
        $this->assertEquals(14, $result->backFansCount);
        $this->assertEquals(77.0, $result->coolingUnits);
    }

    /** @test */
    public function batteries_only_has_single_battery_line_in_breakdown(): void
    {
        $calc = $this->calculator();
        $result = $calc->calculate(new QuotationInput(
            length: 108, width: 15, height: 3.5,
            tiers: 4, lines: 5,
            pricingScope: PoultryPricingScope::BatteriesOnly->value,
            serviceLength: 10,
            birdsPerNest: 16,
            sideFansCount: 6,
            heatersCount: 2,
        ));
        $this->assertCount(1, $result->breakdown);
        $this->assertEquals('battery', $result->breakdown[0]['key']);
    }

    /** @test */
    public function vat_calculation_at_14_percent_egypt(): void
    {
        $calc = $this->calculator();
        $result = $calc->calculate(new QuotationInput(
            length: 81, width: 12, height: 3.5,
            tiers: 4, lines: 4,
            serviceLength: 9,
            sideFansCount: 8,
            heatersCount: 2,
            vatRegion: 'egypt',
        ));
        $expectedVat = bcmul($result->subtotal, '0.14', 2);
        $this->assertEquals($expectedVat, $result->vatAmount);
    }

    /** @test */
    public function vat_disabled_yields_zero_vat(): void
    {
        $calc = $this->calculator();
        $result = $calc->calculate(new QuotationInput(
            length: 81, width: 12, height: 3.5,
            tiers: 4, lines: 4,
            serviceLength: 9,
            sideFansCount: 8,
            heatersCount: 2,
            vatRegion: 'none',
        ));
        $this->assertEquals('0.00', $result->vatAmount);
        $this->assertEquals($result->subtotal, $result->total);
    }

    /** @test */
    public function it_throws_when_length_less_than_service(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $calc = $this->calculator();
        $calc->calculate(new QuotationInput(
            length: 5, width: 12, height: 3.5,
            tiers: 4, lines: 4,
            serviceLength: 10,
        ));
    }

    /** @test */
    public function subtotal_matches_breakdown_sum(): void
    {
        $calc = $this->calculator();
        $result = $calc->calculate(new QuotationInput(
            length: 108, width: 15, height: 3.5,
            tiers: 4, lines: 5,
            serviceLength: 10,
            birdsPerNest: 16,
            sideFansCount: 6,
            heatersCount: 2,
        ));
        $sum = array_sum(array_map(fn ($r) => (float) $r['total'], $result->breakdown));
        $this->assertEquals((float) $result->subtotal, $sum);
    }
}
