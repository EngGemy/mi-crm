<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Services\QuotationGenerator;
use Illuminate\Http\Request;

class PublicQuotationController extends Controller
{
    public function view(Request $request, Quotation $quotation)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'الرابط غير صالح أو منتهي');
        }

        // تسجيل المشاهدة
        $quotation->increment('view_count');
        $quotation->update(['last_viewed_at' => now()]);

        $quotation->load(['customer', 'items', 'quotationType']);

        return view('public.quotation', compact('quotation'));
    }

    public function pdf(Request $request, Quotation $quotation)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'الرابط غير صالح أو منتهي');
        }

        $quotation->increment('view_count');

        return app(QuotationGenerator::class)->streamPdf($quotation);
    }
}
