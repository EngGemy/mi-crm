<?php

namespace Tests\Unit;

use App\Models\PoultryQuotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoultryQuotationNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_quote_number_follows_the_highest_existing_number(): void
    {
        PoultryQuotation::create([
            'quote_number' => 'Q-2026-0055',
            'client_name' => 'سابق',
            'project_type' => 'layer',
            'length' => 81,
            'width' => 12,
            'height' => 4,
            'tiers' => 4,
            'lines' => 4,
            'barns_count' => 1,
        ]);

        $this->assertSame('Q-2026-0056', PoultryQuotation::nextQuoteNumber(2026));

        $quote = PoultryQuotation::create([
            'client_name' => 'جديد',
            'project_type' => 'layer_auto_collect',
            'length' => 127,
            'width' => 15,
            'height' => 4.25,
            'tiers' => 4,
            'lines' => 5,
            'barns_count' => 1,
        ]);

        $this->assertSame('Q-2026-0056', $quote->quote_number);
    }
}
