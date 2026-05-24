<?php

namespace Tests\Unit;

use App\Enums\PoultryProjectType;
use App\Services\Poultry\PoultryTechnicalCalculator;
use PHPUnit\Framework\TestCase;

class PoultryTechnicalCalculatorTest extends TestCase
{
    protected PoultryTechnicalCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new PoultryTechnicalCalculator;
    }

    protected function baseConfig(): array
    {
        return [
            'fan_capacity_kg' => 5000,
            'cooling_pad_meters_per_fan' => 5.5,
            'layer_nest_module_m' => 0.60,
            'layer_birds_per_nest' => 10,
            'layer_max_bird_weight_kg' => 1.7,
            'broiler_weight_birds_map' => PoultryTechnicalCalculator::DEFAULT_BROILER_WEIGHT_MAP,
        ];
    }

    /** @test */
    public function broiler_test_1_full_nest_and_bird_counts(): void
    {
        $result = $this->calc->compute([
            'project_type' => PoultryProjectType::Broiler->value,
            'barn_length' => 108,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 5,
            'bird_weight_kg' => 2.100,
            'birds_per_nest' => 16,
        ], $this->baseConfig());

        $this->assertEquals(98, $result['effective_length']);
        $this->assertEquals(784, $result['nests_per_line']);
        $this->assertEquals(3920, $result['total_nests']);
        $this->assertEquals(62720, $result['total_birds']);
    }

    /** @test */
    public function broiler_test_2_main_fans_from_birds_and_weight(): void
    {
        $fans = $this->calc->exhaustFansFromBirds(36464, 2.100, 5000);
        $this->assertEqualsWithDelta(15.31, $fans['raw_load'], 0.01);
        $this->assertEqualsWithDelta(76574.4, $fans['fan_load_kg'], 0.01);
        $this->assertEquals(16, $fans['fans_count']);
    }

    /** @test */
    public function broiler_fans_use_ceil_birds_times_max_weight_over_5000(): void
    {
        $result = $this->calc->compute([
            'project_type' => PoultryProjectType::Broiler->value,
            'barn_length' => 108,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 5,
            'bird_weight_kg' => 2.100,
            'birds_per_nest' => 16,
        ], $this->baseConfig());

        $this->assertEquals(62720, $result['total_birds']);
        $this->assertEquals(2.1, $result['ventilation_max_weight_kg']);
        $this->assertEquals(27, $result['main_fans_count']);
        $this->assertStringContainsString('ceil(62,720', $result['fan_formula']);
    }

    /** @test */
    public function broiler_air_window_test_odd_length_81(): void
    {
        $this->assertEquals(39, $this->calc->broilerAirWindows(81));
    }

    /** @test */
    public function broiler_air_window_test_even_length_102(): void
    {
        $this->assertEquals(49, $this->calc->broilerAirWindows(102));
    }

    /** @test */
    public function layer_test_1_full_counts(): void
    {
        $result = $this->calc->compute([
            'project_type' => PoultryProjectType::Layer->value,
            'barn_length' => 81,
            'service_length' => 9,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 10,
        ], $this->baseConfig());

        $this->assertEquals(72, $result['effective_length']);
        $this->assertEquals(120, $result['nests_one_side']);
        $this->assertEquals(960, $result['nests_per_line']);
        $this->assertEquals(3840, $result['total_nests']);
        $this->assertEquals(38400, $result['total_birds']);
        $this->assertEquals(0, $result['side_fans_count']);
        $this->assertEquals(0, $result['heaters_count']);
    }

    /** @test */
    public function layer_test_2_rear_fans_and_cooling(): void
    {
        $fans = $this->calc->layerRearFansFromBirds(38400, 1.7);
        $this->assertEqualsWithDelta(13.056, $fans['raw_rear_fan_load'], 0.001);
        $this->assertEquals(14, $fans['rear_fans_count']);
        $this->assertEquals(77.0, $fans['cooling_pad_length_m']);
    }

    /** @test */
    public function broiler_resolves_birds_per_nest_from_weight_mapping(): void
    {
        $this->assertEquals(16, $this->calc->birdsPerNestFromWeight(2.1, $this->baseConfig()));
        $this->assertEquals(21, $this->calc->birdsPerNestFromWeight(1.6, $this->baseConfig()));
    }

    /** @test */
    public function width_to_lines_mapping(): void
    {
        $config = array_merge($this->baseConfig(), [
            'width_lines_map' => PoultryTechnicalCalculator::DEFAULT_WIDTH_LINES_MAP,
        ]);
        $this->assertEquals(5, $this->calc->resolveLinesFromWidth(15, $config));
        $this->assertEquals(4, $this->calc->resolveLinesFromWidth(12, $config));
    }
}
