<?php

namespace Tests\Unit;

use App\Services\QuotationCalculator;
use PHPUnit\Framework\TestCase;

class QuotationCalculatorTest extends TestCase
{
    public function test_item_total_without_discount(): void
    {
        $this->assertSame(500.0, QuotationCalculator::calculateItemTotal([
            'unit_price' => 100,
            'quantity' => 5,
            'discount_percentage' => 0,
        ]));
    }

    public function test_item_total_with_line_discount(): void
    {
        $this->assertSame(450.0, QuotationCalculator::calculateItemTotal([
            'unit_price' => 100,
            'quantity' => 5,
            'discount_percentage' => 10,
        ]));
    }

    public function test_item_total_fractional(): void
    {
        $this->assertSame(99.99, QuotationCalculator::calculateItemTotal([
            'unit_price' => 33.33,
            'quantity' => 3,
            'discount_percentage' => 0,
        ]));
    }

    public function test_quotation_totals_three_lines_discount_vat(): void
    {
        $result = QuotationCalculator::calculateQuotation([
            'items' => [
                ['unit_price' => 500, 'quantity' => 1, 'discount_percentage' => 0],
                ['unit_price' => 1000, 'quantity' => 1, 'discount_percentage' => 0],
                ['unit_price' => 1500, 'quantity' => 1, 'discount_percentage' => 0],
            ],
            'discount_percentage' => 10,
            'vat_percentage' => 14,
            'secondary_currency' => null,
            'exchange_rate' => 0,
        ]);

        $this->assertSame(3000.0, $result['subtotal']);
        $this->assertSame(300.0, $result['discount_amount']);
        $this->assertSame(378.0, $result['vat_amount']);
        $this->assertSame(3078.0, $result['total_amount']);
    }

    public function test_user_example_three_usd_lines_vat_14(): void
    {
        $result = QuotationCalculator::calculateQuotation([
            'items' => [
                ['unit_price' => 46, 'quantity' => 972, 'discount_percentage' => 0],
                ['unit_price' => 38, 'quantity' => 648, 'discount_percentage' => 0],
                ['unit_price' => 4500, 'quantity' => 1, 'discount_percentage' => 0],
            ],
            'discount_percentage' => 0,
            'vat_percentage' => 14,
            'secondary_currency' => null,
            'exchange_rate' => 0,
        ]);

        $this->assertSame(73836.0, $result['subtotal']);
        $this->assertSame(10337.04, $result['vat_amount']);
        $this->assertSame(84173.04, $result['total_amount']);
    }

    public function test_to_float_strips_commas(): void
    {
        $this->assertSame(1500.5, QuotationCalculator::toFloat('1,500.50'));
    }

    public function test_secondary_total_multiplies_exchange_rate(): void
    {
        $result = QuotationCalculator::calculateQuotation([
            'items' => [
                ['unit_price' => 100, 'quantity' => 1, 'discount_percentage' => 0],
            ],
            'discount_percentage' => 0,
            'vat_percentage' => 0,
            'secondary_currency' => 'USD',
            'exchange_rate' => 0.032,
        ]);

        $this->assertSame(100.0, $result['total_amount']);
        $this->assertSame(3.2, $result['total_amount_secondary']);
    }
}
