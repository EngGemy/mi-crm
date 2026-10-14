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
        $this->assertSame('750,000 ج.م', $offer['document']['heating']['amount']);
        $this->assertSame('750,000 ج.م', $offer['document']['electricity']['amount']);
        $this->assertSame('250,000 ج.م', $offer['document']['monitor']['amount']);
        $this->assertSame('4,167,600 ج.م', $offer['document']['total']);
        $this->assertSame('4,167,600 ج.م', $offer['accessories_pdf']['total']);
        $this->assertSame('شفاطات طرد 140×140', $offer['accessories_pdf']['lines'][0][0]);
        $this->assertSame('التبريد', $offer['accessories_pdf']['lines'][1][0]);
        $this->assertSame('الشفاطات الجانبية', $offer['accessories_pdf']['lines'][3][0]);
    }

    public function test_salesperson_override_replaces_the_base_price(): void
    {
        $offer = (new VentilationOffer(new PoultryTechnicalCalculator))->calculate(80952, 114, true, 6, $this->prices(), [
            'heating' => 900000,
            'electricity' => 800000,
        ]);

        $this->assertSame('900,000 ج.م', $offer['document']['heating']['amount']);
        $this->assertSame('800,000 ج.م', $offer['document']['electricity']['amount']);
        $this->assertSame('4,367,600 ج.م', $offer['accessories_pdf']['total']);
    }

    public function test_layers_drop_heating_and_side_fans(): void
    {
        $offer = (new VentilationOffer(new PoultryTechnicalCalculator))->calculate(80952, 81, false, 6, $this->prices());

        $this->assertSame(28, $offer['fans']);
        $this->assertSame(154.0, $offer['cooling_m2']);
        $this->assertSame(78, $offer['inlets']);
        $this->assertNull($offer['document']['heating']);
        $this->assertNull($offer['document']['circulation']);
        $this->assertSame('2,870,600 ج.م', $offer['document']['total']);
        $this->assertSame('شفاطات طرد 140×140', $offer['accessories_pdf']['lines'][0][0]);
        $this->assertSame('التبريد', $offer['accessories_pdf']['lines'][1][0]);
        $labels = array_column($offer['accessories_pdf']['lines'], 0);
        $this->assertNotContains('منظومة التدفئة بالمياه الساخنة', $labels);
        $this->assertNotContains('الشفاطات الجانبية', $labels);
    }

    public function test_side_fan_choice_accepts_only_the_four_options(): void
    {
        $this->assertSame(8, VentilationOffer::sideFanChoice('8'));
        $this->assertNull(VentilationOffer::sideFanChoice(12));
        $this->assertNull(VentilationOffer::sideFanChoice(null));
    }

    public function test_construction_brochure_uses_barn_area_and_a_single_edited_total(): void
    {
        $rates = ['concrete' => 1200, 'steel' => 2450, 'wall' => 1200, 'tanks' => 480000];
        $summary = new ConstructionSummary;

        $base = $summary->lines(81, 12, 4, $rates);
        $this->assertSame('972', $base['concrete']['qty']);
        $this->assertSame('648', $base['walls']['qty']);
        $this->assertSame('1,166,400 ج.م', $base['concrete']['amount']);
        $this->assertSame('2,381,400 ج.م', $base['steel']['amount']);
        $this->assertSame('777,600 ج.م', $base['walls']['amount']);
        $this->assertSame('480,000 ج.م', $base['tanks']['amount']);
        $this->assertSame('4,805,400 ج.م', $base['total']);

        $edited = $summary->lines(81, 12, 4, $rates, 4000000);
        $this->assertSame('4,000,000 ج.م', $edited['total']);
        $this->assertSame('972', $edited['concrete']['qty']);
        $this->assertSame('1,166,400 ج.م', $edited['concrete']['amount']);
    }

    public function test_construction_uses_the_fixed_price_of_the_chosen_type(): void
    {
        $prices = ['sandwich' => 2500000, 'cement' => 3200000];

        $sandwich = (new ConstructionSummary)->chosen('sandwich', $prices);
        $cement = (new ConstructionSummary)->chosen('cement', $prices);

        $this->assertSame(2500000.0, $sandwich['total']);
        $this->assertSame('ساندوتش بانل', $sandwich['rows'][0][1]);
        $this->assertSame('2,500,000 ج.م', $sandwich['rows'][1][1]);
        $this->assertSame(3200000.0, $cement['total']);
        $this->assertStringContainsString('خرسانة', $cement['message']);
        $this->assertStringContainsString('3,200,000', $cement['message']);
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
            'heating' => 750000,
            'electricity' => 750000,
            'monitor' => 250000,
        ];
    }
}
