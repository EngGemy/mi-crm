<?php

namespace App\Http\Controllers;

use App\Models\PoultryQuotation;
use App\Services\Pricing\PricingCardImageGenerator;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PoultryShareCardController extends Controller
{
    public function show(PoultryQuotation $record): View
    {
        try {
            $path = app(PricingCardImageGenerator::class)->generate($record);
            if ($record->image_path !== $path) {
                $record->forceFill(['image_path' => $path])->saveQuietly();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $record->refresh();

        $imageUrl = null;
        if (is_string($record->image_path) && str_ends_with(strtolower($record->image_path), '.png')) {
            $imageUrl = $record->image_url;
            $file = storage_path('app/'.$record->image_path);
            if (is_string($imageUrl) && is_file($file)) {
                $imageUrl .= '?v='.filemtime($file);
            }
        }

        $company = 'إم آي للصناعات المعدنية';
        try {
            $fromSettings = settings('company.name_ar');
            if (is_string($fromSettings) && trim($fromSettings) !== '') {
                $company = trim($fromSettings);
            }
        } catch (\Throwable) {
        }

        return view('poultry.share-card', [
            'quotation' => $record,
            'imageUrl' => $imageUrl,
            'pdfUrl' => URL::temporarySignedRoute(
                'poultry-quotations.welcome-pdf',
                now()->addDays(45),
                ['record' => $record->getKey()]
            ),
            'company' => $company,
            'total' => $this->total($record),
        ]);
    }

    private function total(PoultryQuotation $quotation): float
    {
        $total = (float) $quotation->total;
        if ($total > 0) {
            return $total;
        }

        $financial = $quotation->pricing_snapshot['financial'] ?? [];

        return (float) ($financial['total'] ?? $financial['grand_total'] ?? $quotation->subtotal ?? 0);
    }
}
