<?php

namespace Tests\Unit;

use App\Support\BroilerWeightReference;
use PHPUnit\Framework\TestCase;

class BroilerWeightReferenceTest extends TestCase
{
    /** @test */
    public function it_maps_approved_weights_to_birds_per_nest(): void
    {
        $map = [
            '1.600' => 21,
            '1.850' => 18,
            '2.100' => 16,
            '2.650' => 13,
            '2.800' => 12,
        ];

        foreach ($map as $weight => $birds) {
            $this->assertEquals($birds, BroilerWeightReference::birdsPerNest((float) $weight, $map));
        }
    }

    /** @test */
    public function it_calculates_total_birds_from_nests(): void
    {
        $map = [
            '1.600' => 21, '1.850' => 18, '2.100' => 16, '2.650' => 13, '2.800' => 12,
        ];
        $totalNests = 3920;
        $rows = BroilerWeightReference::rows($map);
        $birds210 = BroilerWeightReference::birdsPerNest(2.1, $map);
        $this->assertEquals(16, $birds210);
        $this->assertEquals(62720, $totalNests * $birds210);
    }
}
