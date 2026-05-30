<?php

use App\Http\Controllers\Api\PoultryPricingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PublicQuotationController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// PDF حاسبة أسعار الدواجن
Route::middleware(['auth'])->group(function () {
    Route::get('/poultry-quotations/{record}/pdf', function (App\Models\PoultryQuotation $record) {
        return app(App\Services\PoultryQuotationPdfGenerator::class)->download($record);
    })->name('poultry-quotations.pdf');
});

// مسارات العقود (محمية بالمصادقة)
Route::middleware(['auth'])->group(function () {
    Route::get('/contracts/{contract}/preview', [ContractController::class, 'preview'])
        ->name('contracts.preview');
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])
        ->name('contracts.download');
    Route::get('/contracts/{contract}/html', [ContractController::class, 'html'])
        ->name('contracts.html');

    Route::get('/quotations/{quotation}/preview', [QuotationController::class, 'preview'])
        ->name('quotations.preview');
    Route::get('/quotations/{quotation}/download', [QuotationController::class, 'download'])
        ->name('quotations.download');
    Route::get('/quotations/{quotation}/html', [QuotationController::class, 'html'])
        ->name('quotations.html');
    Route::get('/quotations/{quotation}/image', [QuotationController::class, 'image'])
        ->name('quotations.image');
    Route::get('/quotations/{quotation}/image/download', [QuotationController::class, 'imageDownload'])
        ->name('quotations.image.download');
});

// روابط معاينة عامة مع توقيع آمن
Route::get('/q/{quotation:quotation_number}', [PublicQuotationController::class, 'view'])
    ->middleware('signed')
    ->name('quotations.public');

Route::get('/q/{quotation:quotation_number}/pdf', [PublicQuotationController::class, 'pdf'])
    ->middleware('signed')
    ->name('quotations.public.pdf');

// API حساب مباشر (Filament + Sales)
Route::middleware(['auth'])->prefix('api/poultry')->name('api.poultry.')->group(function () {
    Route::post('/calculate', [PoultryPricingController::class, 'calculate'])
        ->name('calculate');
});

// وحدة تسعير المبيعات — حاسبة عروض الأسعار
Route::middleware(['auth'])->prefix('sales')->name('sales.')->group(function () {
    Route::get('/quotations', [App\Http\Controllers\Sales\QuotationController::class, 'index'])
        ->name('quotations.index');
    Route::get('/quotations/create', [App\Http\Controllers\Sales\QuotationController::class, 'create'])
        ->name('quotations.create');
    Route::post('/quotations', [App\Http\Controllers\Sales\QuotationController::class, 'store'])
        ->name('quotations.store');
    Route::get('/quotations/{quotation}', [App\Http\Controllers\Sales\QuotationController::class, 'show'])
        ->name('quotations.show');
    Route::post('/quotations/calculate', [App\Http\Controllers\Sales\QuotationController::class, 'calculateJson'])
        ->name('quotations.calculate');
    Route::post('/quotations/preview-pdf', [App\Http\Controllers\Sales\QuotationController::class, 'previewPdf'])
        ->name('quotations.preview-pdf');
    Route::get('/quotations/{quotation}/image', [App\Http\Controllers\Sales\QuotationController::class, 'image'])
        ->name('quotations.image');
    Route::get('/quotations/{quotation}/contract', [App\Http\Controllers\Sales\QuotationController::class, 'contract'])
        ->name('quotations.contract');
    Route::get('/quotations/{quotation}/whatsapp', [App\Http\Controllers\Sales\QuotationController::class, 'whatsapp'])
        ->name('quotations.whatsapp');
});
