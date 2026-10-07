<?php

namespace App\Console\Commands;

use App\Models\PoultryQuotation;
use App\Quotations\Templates\LayersQuotationTemplate;
use Illuminate\Console\Command;

class SampleLayersQuotation extends Command
{
    protected $signature = 'quotations:sample-layers';

    protected $description = 'يولّد PDF تجريبي لعرض البياض للمراجعة البصرية';

    public function handle(): int
    {
        $totalCents = 678912000;
        $subtotalCents = (int) round($totalCents / 1.14);
        $vatCents = $totalCents - $subtotalCents;

        $quote = new PoultryQuotation([
            'quote_number' => 'Q-2026-0001',
            'client_name' => 'م/ محمد جامع',
            'client_location' => 'دمياط',
            'project_type' => 'layer',
            'length' => 81,
            'width' => 11.5,
            'height' => 3.5,
            'tiers' => 4,
            'lines' => 4,
            'barns_count' => 1,
            'birds_per_nest' => 10,
            'bird_count' => 38400,
            'total_nests' => 3840,
            'nests_per_line' => 960,
            'exchange_rate' => 52,
            'subtotal' => $subtotalCents / 100,
            'vat_amount' => $vatCents / 100,
            'total' => $totalCents / 100,
            'pricing_snapshot' => [
                'project_type' => 'layer',
                'technical' => [
                    'effective_length' => 72,
                    'tiers' => 4,
                    'lines' => 4,
                    'nests_one_side' => 120,
                    'total_nests' => 3840,
                    'total_birds' => 38400,
                    'birds_per_nest' => 10,
                    'layer_nest_module_m' => 0.60,
                ],
                'computed' => [
                    'effective_length' => 72,
                    'bird_count' => 38400,
                    'total_nests' => 3840,
                ],
                'financial' => [
                    'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                    'vat_amount' => number_format($vatCents / 100, 2, '.', ''),
                    'total' => number_format($totalCents / 100, 2, '.', ''),
                ],
                'currency' => ['rate' => 52],
            ],
        ]);
        $quote->issued_at = '2026-10-01';

        $directory = storage_path('app/quotations/samples');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error('تعذر إنشاء مجلد العينات.');

            return self::FAILURE;
        }

        $path = $directory.DIRECTORY_SEPARATOR.'layers_sample.pdf';
        file_put_contents($path, (new LayersQuotationTemplate)->pdfBytes($quote));
        $this->info($path);

        return self::SUCCESS;
    }
}
