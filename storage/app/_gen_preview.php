<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = App\Models\PoultryQuotation::find(3);
if (! $q) {
    fwrite(STDERR, "quotation 3 missing\n");
    exit(1);
}

$out = storage_path('app/pdf-templates/_preview-q3.pdf');
app(App\Services\Poultry\MiProposalPdfGenerator::class)->saveTo($q, $out);
echo "saved {$out}\n";

$previewDir = 'C:\\Users\\SPEED LAP\\.cursor\\projects\\d-laragon-www-mi-mi-laravel-app\\attachments\\718f0e51-ee7e-440b-b63d-e0629ce3a477\\preview';
if (! is_dir($previewDir)) {
    mkdir($previewDir, 0755, true);
}

if (class_exists('Imagick')) {
    // optional
}

echo "ok\n";
