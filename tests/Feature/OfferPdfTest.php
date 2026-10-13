<?php

namespace Tests\Feature;

use App\Models\PoultryQuotation;
use App\Quotations\Pdf\OfferPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_ventilation_and_accessories_quotes_are_pdf_files(): void
    {
        $quote = new PoultryQuotation([
            'quote_number' => 'Q-VENT',
            'client_name' => 'أحمد علي',
            'client_phone' => '01012345678',
            'project_type' => 'broiler',
            'length' => 114,
            'width' => 17,
            'height' => 4.1,
            'bird_count' => 80952,
            'ventilation_side_fans' => 6,
        ]);

        $pdf = app(OfferPdf::class);
        $ventilation = $pdf->ventilation($quote);
        $accessories = $pdf->accessories($quote);

        $this->assertStringStartsWith('%PDF', $ventilation);
        $this->assertStringStartsWith('%PDF', $accessories);
        $this->assertGreaterThan(1000, strlen($ventilation));
        $this->assertGreaterThan(1000, strlen($accessories));
    }
}
