<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::query()->first();
if (! $user) {
    $user = App\Models\User::query()->create([
        'name' => 'Admin',
        'email' => 'admin@mi.test',
        'password' => bcrypt('password'),
    ]);
    if (method_exists($user, 'assignRole')) {
        try {
            $user->assignRole('super_admin');
        } catch (Throwable) {
        }
    }
}

$q = new App\Models\PoultryQuotation([
    'client_name' => 'أحمد نزار عدنان اليازجي',
    'client_phone' => '+201000000000',
    'project_type' => 'broiler',
    'pricing_scope' => 'batteries_only',
    'length' => 81,
    'width' => 12,
    'height' => 3.7,
    'tiers' => 4,
    'lines' => 4,
    'barns_count' => 1,
    'bird_weight_kg' => 2.100,
    'bird_price' => settings('poultry_pricing.price_per_bird', 95),
    'exchange_rate' => settings('poultry_pricing.egp_to_usd_rate', 48),
    'status' => 'draft',
    'vat_percentage' => 0,
    'created_by' => $user->id,
]);
$q->save();
$q->autoCompute();
$q->saveQuietly();

echo "created #{$q->id} {$q->quote_number} total={$q->total} birds={$q->bird_count} nests={$q->total_nests} eff=".($q->pricing_snapshot['computed']['effective_length'] ?? '?').PHP_EOL;

$out = storage_path('app/pdf-templates/_preview-q3.pdf');
app(App\Services\Poultry\MiProposalPdfGenerator::class)->saveTo($q, $out);
echo "pdf {$out}\n";
