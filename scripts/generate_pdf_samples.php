<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PoultryQuotation;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use App\Services\PoultryHousePricingService;
use App\Services\QuotationGenerator;
use App\Services\ContractGenerator;
use Illuminate\Support\Facades\Storage;

// تهيئة مستخدم وعميل
$user = User::firstOrCreate(
    ['email' => 'test@mi.com'],
    ['name' => 'مدير النظام', 'password' => bcrypt('password')]
);

$customer = Customer::firstOrCreate(
    ['phone' => '01000000000'],
    ['name' => 'عميل تجريبي للاختبار', 'address' => 'القاهرة']
);

echo "=== توليد عرض سعر دواجن ===\n";

// توليد عرض سعر دواجن مع snapshot
$service = app(PoultryHousePricingService::class);
$input = [
    'project_type' => 'broiler',
    'pricing_scope' => 'full_project',
    'hall_length' => 120,
    'hall_width' => 16,
    'hall_height' => 4.5,
    'service_length' => 10,
    'tiers' => 4,
    'lines' => 4,
    'bird_weight_kg' => 2.1,
    'birds_per_nest' => null,
    'side_fans_count' => null,
    'heaters_count' => null,
    'wall_type' => null,
    'vat_region' => 'egypt',
];

$result = $service->compute($input);

$pq = PoultryQuotation::create([
    'client_name' => 'عميل تجريبي',
    'client_phone' => '01000000000',
    'client_address' => 'القاهرة',
    'project_type' => 'broiler',
    'pricing_scope' => 'full_project',
    'length' => 120,
    'width' => 16,
    'height' => 4.5,
    'service_length' => 10,
    'tiers' => 4,
    'lines' => 4,
    'bird_weight_kg' => 2.1,
    'vat_percentage' => 14,
    'pricing_snapshot' => $result,
    'subtotal' => $result['subtotal'],
    'vat_amount' => $result['financial']['vat_amount'] ?? 0,
    'total' => $result['financial']['total'] ?? 0,
    'status' => 'approved',
    'created_by' => $user->id,
]);

// توليد PDF عرض السعر
$html = view('quotations.template', [
    'quotation' => $pq,
    'customer' => (object) ['name' => 'عميل تجريبي'],
    'type' => null,
    'items' => collect(),
    'groupedItems' => [],
    'groupSubtotals' => [],
    'sectionAttachments' => collect(),
    'renderedTerms' => [],
    'technicalSpecs' => collect(),
    'images' => collect(),
    'coverImage' => null,
    'settings' => settings()->all(),
])->render();

$pdfPath = storage_path('app/public/sample_quotation_calc_sync.pdf');
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'cairo',
    'directionality' => 'rtl',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 20,
    'margin_bottom' => 25,
]);
$mpdf->WriteHTML($html);
$mpdf->Output($pdfPath, 'F');
echo "✅ عرض السعر: $pdfPath\n";

// توليد PDF عقد
$contractType = \App\Models\ContractType::firstOrCreate(['name' => 'عقد تجريبي'], ['code' => 'TEST']);

$contract = Contract::create([
    'contract_number' => 'CNT-2026-TEST-001',
    'contract_date' => now(),
    'customer_id' => $customer->id,
    'contract_type_id' => $contractType->id,
    'status' => 'draft',
    'project_code' => 'PRJ-TEST-001',
    'project_name' => 'مشروع تجريبي',
    'installation_location' => 'دمياط',
    'total_amount' => $result['financial']['total'] ?? 0,
    'vat_amount' => $result['financial']['vat_amount'] ?? 0,
    'subtotal' => $result['subtotal'],
]);

$contractGenerator = app(ContractGenerator::class);
$contractHtml = view('contracts.template', [
    'contract' => $contract,
])->render();

$contractPdfPath = storage_path('app/public/sample_contract_dual_signature.pdf');
$mpdf2 = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'cairo',
    'directionality' => 'rtl',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 20,
    'margin_bottom' => 32,
]);
$mpdf2->WriteHTML($contractHtml);
$mpdf2->Output($contractPdfPath, 'F');
echo "✅ العقد: $contractPdfPath\n";

echo "\n=== تم التوليد بنجاح ===\n";
