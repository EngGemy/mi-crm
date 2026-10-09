<?php

use App\Models\PoultryQuotation;
use App\Services\Poultry\MiProposalPdfGenerator;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = new PoultryQuotation([
    'client_name' => 'م/ محمد جامع',
    'client_phone' => '01000000000',
    'client_location' => 'المنوفية — أشمون',
    'project_type' => 'broiler',
    'pricing_scope' => 'full_project',
    'length' => 100,
    'width' => 14,
    'height' => 3.7,
    'tiers' => 4,
    'lines' => 4,
    'barns_count' => 2,
    'service_length' => 10,
    'bird_weight_kg' => 2.1,
    'bird_price' => 95,
    'exchange_rate' => 50,
    'wall_type' => 'sandwich',
    'roof_type' => 'gable',
    'internal_columns' => 2,
    'include_monitor' => true,
    'include_electricity' => true,
    'quote_number' => 'Q-2026-PREVIEW',
    'status' => 'draft',
]);
$q->issued_at = now();
$q->created_at = now();
$q->autoCompute();

$path = public_path('quote-layout-preview.pdf');
app(MiProposalPdfGenerator::class)->saveTo($q, $path);

echo 'birds='.$q->bird_count.' total='.$q->total.PHP_EOL;
echo 'saved '.filesize($path).' '.$path.PHP_EOL;
