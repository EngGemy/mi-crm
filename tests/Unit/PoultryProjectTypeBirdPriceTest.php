<?php

namespace Tests\Unit;

use App\Enums\PoultryProjectType;
use PHPUnit\Framework\TestCase;

class PoultryProjectTypeBirdPriceTest extends TestCase
{
    public function test_bird_price_in_dollars_follows_type_and_tiers(): void
    {
        $this->assertSame(2.8, PoultryProjectType::Broiler->birdPriceUsd(4));
        $this->assertSame(2.9, PoultryProjectType::Broiler->birdPriceUsd(3));
        $this->assertSame(3.5, PoultryProjectType::BroilerAutoExit->birdPriceUsd(4));
        $this->assertSame(3.6, PoultryProjectType::BroilerAutoExit->birdPriceUsd(3));
        $this->assertSame(3.1, PoultryProjectType::Layer->birdPriceUsd(4));
        $this->assertSame(3.2, PoultryProjectType::Layer->birdPriceUsd(3));
        $this->assertSame(3.4, PoultryProjectType::LayerAutoCollect->birdPriceUsd(4));
        $this->assertSame(3.5, PoultryProjectType::LayerAutoCollect->birdPriceUsd(3));
        $this->assertNull(PoultryProjectType::LayerRearing->birdPriceUsd(4));
    }

    public function test_quote_type_options_are_the_five_named_types(): void
    {
        $this->assertSame([
            'تسمين',
            'تسمين تخريج آلي',
            'إنتاج بياض',
            'إنتاج بياض جمع آلي',
            'تربية بياض',
        ], array_values(PoultryProjectType::options()));
    }
}
