<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = App\Models\PoultryQuotation::find(3);
$s = $q->pricing_snapshot ?? [];
echo json_encode([
    'eff' => $s['computed']['effective_length'] ?? null,
    'nests' => $s['computed']['total_nests'] ?? $q->total_nests,
    'npl' => $s['computed']['nests_per_line'] ?? $q->nests_per_line,
    'tiers' => $q->tiers,
    'lines' => $q->lines,
    'len' => $q->length,
    'bat' => $q->battery_cost,
    'sub' => $q->subtotal,
    'rate' => $q->exchange_rate,
    'scope' => $q->pricing_scope,
    'type' => $q->project_type,
    'w' => $q->bird_weight_kg,
    'name' => $q->client_name,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
