<?php

namespace Tests\Unit;

use App\Services\Poultry\ConstructionSummary;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Services\Poultry\VentilationOffer;
use PHPUnit\Framework\TestCase;

class VentilationOfferTest extends TestCase
{
    public function test_broiler_sample_matches_the_ventilation_brochure_quantities(): void
    {
        $offer = (new VentilationOffer(new PoultryTechnicalCalculator))->calculate(80952, 114, true, 6, $this->prices());

        $this->assertSame(34, $offer['fans']);
        $this->assertSame(187.0, $offer['cooling_m2']);
        $this->assertSame(110, $offer['inlets']);
        $this->assertSame('34', $offer['document']['fans']['qty']);
        $this->assertSame('187', $offer['document']['cooling']['qty']);
        $this->assertSame('6', $offer['document']['circulation']['qty']);
        $this->assertSame('110', $offer['document']['inlets']['qty']);
        $this->assertSame('من 750,000 إلى 1,100,000 ج.م', $offer['document']['heating']['amount']);
        $this->assertSame('من 750,000 إلى 1,000,000 ج.م', $offer['document']['electricity']['amount']);
        $this->assertSame('250,000 ج.م', $offer['document']['monitor']['amount']);
        $this->assertSame('من 4,167,600 إلى 4,767,600 ج.م', $offer['document']['total']);
    }

    public function test_layers_drop_heating_and_side_fans(): void
    {
        $offer = (new VentilationOffer(new PoultryTechnicalCalculator))->calculate(80952, 81, false, 6, $this->prices());

        $this->assertSame(28, $offer['fans']);
        $this->assertSame(154.0, $offer['cooling_m2']);
        $this->assertSame(78, $offer['inlets']);
        $this->assertNull($offer['document']['heating']);
        $this->assertNull($offer['document']['circulation']);
        $this->assertSame('من 2,870,600 إلى 3,120,600 ج.م', $offer['document']['total']);
    }

    public function test_side_fan_choice_accepts_only_the_four_options(): void
    {
        $this->assertSame(8, VentilationOffer::sideFanChoice('8'));
        $this->assertNull(VentilationOffer::sideFanChoice(12));
        $this->assertNull(VentilationOffer::sideFanChoice(null));
    }

    public function test_construction_follows_barn_dimensions(): void
    {
        $summary = (new ConstructionSummary)->lines(10, 5, 4, [
            'concrete' => 1000,
            'steel' => 2000,
            'wall' => 500,
            'tanks' => 400000,
            'include_tanks' => true,
        ]);

        $this->assertSame(590000.0, $summary['total']);
        $this->assertStringContainsString('10 × 5 × 4', $summary['message']);
    }

    /** @return array<string, float> */
    private function prices(): array
    {
        return [
            'broiler_weight' => 2.1,
            'layer_weight' => 1.7,
            'fan_capacity' => 5000,
            'cooling_per_fan' => 5.5,
            'fan_unit' => 34000,
            'cooling_m2' => 4800,
            'circulation_unit' => 18500,
            'inlet_unit' => 2300,
            'heating_min' => 750000,
            'heating_max' => 1100000,
            'electricity_min' => 750000,
            'electricity_max' => 1000000,
            'monitor' => 250000,
        ];
    }
}
