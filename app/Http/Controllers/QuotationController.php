<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Services\QuotationGenerator;
use App\Services\QuotationImageGenerator;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationGenerator $generator,
        protected QuotationImageGenerator $imageGenerator,
    ) {}

    public function preview(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation = $this->generator->loadRelations($quotation);
        $groupedItems = $this->generator->groupItemsBySection($quotation);
        $renderedTerms = $this->generator->renderTerms($quotation);

        return view('quotations.preview', [
            'quotation' => $quotation,
            'customer' => $quotation->customer,
            'type' => $quotation->quotationType,
            'items' => $quotation->items,
            'groupedItems' => $groupedItems,
            'sectionAttachments' => $quotation->sectionAttachments->where('is_visible', true)->sortBy('sort_order'),
            'renderedTerms' => $renderedTerms,
            'technicalSpecs' => $quotation->technicalSpecs->sortBy('sort_order'),
            'images' => $quotation->images->sortBy('sort_order'),
            'coverImage' => $quotation->images->where('position', 'cover')->first(),
            'settings' => settings()->all(),
        ]);
    }

    public function download(Quotation $quotation)
    {
        return $this->generator->downloadPdf($quotation);
    }

    public function html(Quotation $quotation)
    {
        return $this->generator->renderHtml($quotation);
    }

    public function image(Quotation $quotation)
    {
        $this->authorize('view', $quotation);
        $path = $this->imageGenerator->generate($quotation);

        return redirect(\Storage::disk('public')->url($path));
    }

    public function imageDownload(Quotation $quotation)
    {
        $this->authorize('view', $quotation);
        $path = $this->imageGenerator->getPublicPath($quotation);

        return response()->download($path, "{$quotation->quotation_number}.png");
    }
}
