<?php

namespace Tests\Unit;

use App\Support\SiloCapacity;
use PHPUnit\Framework\TestCase;

class SiloCapacityTest extends TestCase
{
    public function test_silo_tons_follow_the_bird_count_bands(): void
    {
        $this->assertNull(SiloCapacity::tonsForBirdCount(0));
        $this->assertSame(11, SiloCapacity::tonsForBirdCount(42_000));
        $this->assertSame(14, SiloCapacity::tonsForBirdCount(42_001));
        $this->assertSame(14, SiloCapacity::tonsForBirdCount(51_999));
        $this->assertSame(17, SiloCapacity::tonsForBirdCount(52_000));
        $this->assertSame(17, SiloCapacity::tonsForBirdCount(62_000));
        $this->assertSame(25, SiloCapacity::tonsForBirdCount(62_001));
        $this->assertSame(25, SiloCapacity::tonsForBirdCount(80_000));
        $this->assertSame(25, SiloCapacity::tonsForBirdCount(90_000));
    }

    public function test_label_uses_the_bird_band_before_a_saved_choice(): void
    {
        $this->assertSame('14 طن', SiloCapacity::labelForBirds(50_000, '11 طن'));
        $this->assertSame('25 طن', SiloCapacity::labelForBirds(0, null));
        $this->assertSame('17 طن', SiloCapacity::labelForBirds(0, '17 طن'));
    }

    public function test_silo_count_opens_only_above_sixty_two_thousand_birds(): void
    {
        $this->assertFalse(SiloCapacity::allowsSiloCountChoice(62_000));
        $this->assertTrue(SiloCapacity::allowsSiloCountChoice(62_001));
        $this->assertTrue(SiloCapacity::allowsSiloCountChoice(80_000));
    }
}
