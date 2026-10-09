<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = new App\Models\PoultryQuotation([
    'client_name' => 'test',
    'project_type' => 'broiler',
    'length' => 81,
    'width' => 12,
    'height' => 4,
    'tiers' => 4,
    'lines' => 4,
    'barns_count' => 1,
    'quote_number' => 'Q-TEST',
    'status' => 'draft',
    'exchange_rate' => 50,
    'bird_price' => 95,
    'pricing_snapshot' => [
        'project_type' => 'broiler',
        'financial' => [
            'subtotal' => '100.00',
            'vat_amount' => '0.00',
            'total' => '100.00',
        ],
        'currency' => ['rate' => 50],
        'technical' => [
            'effective_length' => 71,
            'tiers' => 4,
            'lines' => 4,
        ],
        'computed' => [
            'effective_length' => 71,
            'total_nests' => 100,
            'bird_count' => 1000,
        ],
    ],
]);
$q->issued_at = now();
$q->created_at = now();
$q->setRelation('siloCapacity', new App\Models\Lookup(['label_ar' => '25 طن']));

$path = __DIR__.'/page7-check.pdf';
app(App\Services\Poultry\MiProposalPdfGenerator::class)->saveTo($q, $path);
echo 'saved '.filesize($path).PHP_EOL;
