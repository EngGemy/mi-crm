<?php

namespace App\Http\Controllers\Sales;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Http\Controllers\Controller;
use App\Models\PoultryQuotation;
use App\Models\PoultryQuotationSnapshot;
use App\Services\PoultryQuotationToContractConverter;
use App\Services\Pricing\DTOs\QuotationInput;
use App\Services\Pricing\PricingCalculator;
use App\Services\Pricing\QuotationImageGenerator;
use App\Support\BroilerWeightReference;
use App\Support\CurrencyConverter;
use App\Support\HeaterOptions;
use App\Support\PoultrySectionLabels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationImageGenerator $imageGenerator,
    ) {}

    public function index()
    {
        $quotations = PoultryQuotation::with('creator')
            ->latest()
            ->paginate(20);

        return view('sales.quotations.index', compact('quotations'));
    }

    public function create()
    {
        return view('sales.quotations.create', [
            'projectTypes' => PoultryProjectType::options(),
            'pricingScopes' => PoultryPricingScope::options(),
            'weightOptions' => BroilerWeightReference::selectOptions(),
            'weightTableRows' => BroilerWeightReference::rows(),
            'heaterOptions' => HeaterOptions::selectOptions(),
            'usdRate' => CurrencyConverter::egpToUsdRate(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_address' => 'nullable|string|max:500',
            'project_type' => ['required', Rule::enum(PoultryProjectType::class)],
            'pricing_scope' => ['required', Rule::enum(PoultryPricingScope::class)],
            'length' => 'required|numeric|min:1',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'service_length' => 'nullable|numeric|min:0',
            'tiers' => 'required|integer|min:1',
            'lines' => 'required|integer|min:1',
            'bird_weight_kg' => 'nullable|numeric|min:0',
            'birds_per_nest' => 'nullable|integer|min:1',
            'side_fans_count' => 'nullable|integer|min:0',
            'heaters_count' => ['nullable', 'integer', Rule::in([0, 3, 4, 5, 6, 8])],
            'wall_type' => 'nullable|string|in:sandwich,cement',
            'vat_region' => ['required', 'string', Rule::in(['none', 'egypt', 'ksa'])],
        ]);

        $calc = PricingCalculator::fromSettings();

        $input = new QuotationInput(
            length: (float) $validated['length'],
            width: (float) $validated['width'],
            height: (float) $validated['height'],
            tiers: (int) $validated['tiers'],
            lines: (int) $validated['lines'],
            projectType: $validated['project_type'],
            pricingScope: $validated['pricing_scope'],
            serviceLength: isset($validated['service_length']) ? (float) $validated['service_length'] : null,
            birdWeightKg: isset($validated['bird_weight_kg']) ? (float) $validated['bird_weight_kg'] : 2.1,
            birdsPerNest: $validated['birds_per_nest'] ?? null,
            sideFansCount: $validated['side_fans_count'] ?? null,
            heatersCount: $validated['heaters_count'] ?? null,
            wallType: $validated['wall_type'] ?? null,
            vatRegion: $validated['vat_region'],
        );

        $result = $calc->calculate($input);

        $quotation = PoultryQuotation::create([
            'client_name' => $validated['client_name'],
            'client_phone' => $validated['client_phone'],
            'client_address' => $validated['client_address'],
            'project_type' => $validated['project_type'],
            'pricing_scope' => $validated['pricing_scope'],
            'length' => $validated['length'],
            'width' => $validated['width'],
            'height' => $validated['height'],
            'service_length' => $validated['service_length'] ?? null,
            'wall_type' => $validated['wall_type'] ?? null,
            'tiers' => $validated['tiers'],
            'lines' => $validated['lines'],
            'bird_weight_kg' => $validated['bird_weight_kg'] ?? null,
            'birds_per_nest' => $validated['birds_per_nest'] ?? null,
            'side_fans_count' => $result->sideFansCount,
            'heaters_count' => $result->heatersCount,
            'bird_count' => $result->birdCount,
            'total_nests' => $result->totalNests,
            'nests_per_line' => $result->nestsPerLine,
            'back_fans_count' => $result->backFansCount,
            'cooling_units' => $result->coolingUnits,
            'windows_count' => $result->windowsCount,
            'concrete_cost' => $result->concreteCost,
            'steel_cost' => $result->steelCost,
            'walls_cost' => $result->wallsCost,
            'tanks_cost' => $result->tanksCost,
            'battery_cost' => $result->batteryCost,
            'back_fans_cost' => $result->backFansCost,
            'cooling_cost' => $result->coolingCost,
            'windows_cost' => $result->windowsCost,
            'side_fans_cost' => $result->sideFansCost,
            'heaters_cost' => $result->heatersCost,
            'control_cost' => $result->controlCost,
            'subtotal' => $result->subtotal,
            'vat_amount' => $result->vatAmount,
            'total' => $result->total,
            'vat_percentage' => match ($validated['vat_region']) {
                'egypt' => 14,
                'ksa' => 15,
                default => 0,
            },
            'pricing_snapshot' => $result->toArray(),
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        PoultryQuotationSnapshot::fromCalculation(
            $quotation,
            [],
            $input->toArray(),
            $result->toArray()
        );

        try {
            $imagePath = $this->imageGenerator->generate($quotation, $result);
            $quotation->update(['image_path' => $imagePath]);
        } catch (\Throwable) {
            // image generation optional if Playwright unavailable
        }

        return redirect()->route('sales.quotations.show', $quotation)
            ->with('success', 'تم إنشاء عرض السعر بنجاح');
    }

    public function show(PoultryQuotation $quotation)
    {
        $quotation->load(['snapshot', 'contract']);

        return view('sales.quotations.show', compact('quotation'));
    }

    public function image(PoultryQuotation $quotation)
    {
        if (! $quotation->image_path) {
            abort(404, 'Image not found');
        }

        $path = str_replace('public/', '', $quotation->image_path);
        if (Storage::disk('public')->exists($path)) {
            return response()->file(Storage::disk('public')->path($path));
        }

        if (Storage::disk('local')->exists($quotation->image_path)) {
            return response()->file(Storage::disk('local')->path($quotation->image_path));
        }

        abort(404, 'Image not found');
    }

    public function contract(PoultryQuotation $quotation, PoultryQuotationToContractConverter $converter)
    {
        if ($quotation->contract_id) {
            return redirect()->route('contracts.download', $quotation->contract_id);
        }

        $quotation->update(['status' => 'accepted']);
        $contract = $converter->convert($quotation->fresh());

        $html = view('contracts.template', [
            'quotation' => $quotation->fresh(),
            'contract' => $contract,
            'customer' => (object) [
                'name' => $quotation->client_name,
                'phone' => $quotation->client_phone,
                'address' => $quotation->client_address,
            ],
            'settings' => settings()->all(),
        ])->render();

        $pdf = PDF::loadHTML($html, [
            'default_font' => 'cairo',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'setAutoTopMargin' => 'pad',
            'setAutoBottomMargin' => 'pad',
            'custom_font_dir' => storage_path('fonts/'),
            'custom_font_data' => [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ]);

        return $pdf->download("Contract_{$quotation->quote_number}.pdf");
    }

    public function whatsapp(PoultryQuotation $quotation)
    {
        return redirect()->away($quotation->whatsapp_share_url);
    }

    public function calculateJson(Request $request)
    {
        $validated = $request->validate([
            'project_type' => ['nullable', Rule::enum(PoultryProjectType::class)],
            'pricing_scope' => ['nullable', Rule::enum(PoultryPricingScope::class)],
            'length' => 'required|numeric|min:1',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'service_length' => 'nullable|numeric|min:0',
            'tiers' => 'required|integer|min:1',
            'lines' => 'required|integer|min:1',
            'bird_weight_kg' => 'nullable|numeric|min:0',
            'birds_per_nest' => 'nullable|integer|min:1',
            'side_fans_count' => 'nullable|integer|min:0',
            'heaters_count' => ['nullable', 'integer', Rule::in([0, 3, 4, 5, 6, 8])],
            'wall_type' => 'nullable|string',
            'vat_region' => ['nullable', 'string', Rule::in(['none', 'egypt', 'ksa'])],
        ]);

        $calc = PricingCalculator::fromSettings();

        $input = new QuotationInput(
            length: (float) $validated['length'],
            width: (float) $validated['width'],
            height: (float) $validated['height'],
            tiers: (int) $validated['tiers'],
            lines: (int) $validated['lines'],
            projectType: $validated['project_type'] ?? PoultryProjectType::Broiler->value,
            pricingScope: $validated['pricing_scope'] ?? PoultryPricingScope::FullProject->value,
            serviceLength: isset($validated['service_length']) ? (float) $validated['service_length'] : null,
            birdWeightKg: isset($validated['bird_weight_kg']) ? (float) $validated['bird_weight_kg'] : 2.1,
            birdsPerNest: $validated['birds_per_nest'] ?? null,
            sideFansCount: $validated['side_fans_count'] ?? null,
            heatersCount: $validated['heaters_count'] ?? null,
            wallType: $validated['wall_type'] ?? null,
            vatRegion: $validated['vat_region'] ?? 'none',
        );

        $result = $calc->calculate($input);

        return response()->json(array_merge($result->toArray(), [
            'section_subtotals' => $result->sectionSubtotals,
            'section_labels' => PoultrySectionLabels::labelsAr(),
            'effective_length' => $result->effectiveLength,
            'nests_per_line' => $result->nestsPerLine,
            'total_nests' => $result->totalNests,
        ]));
    }

    public function previewPdf(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'project_type' => ['nullable', Rule::enum(PoultryProjectType::class)],
            'pricing_scope' => ['nullable', Rule::enum(PoultryPricingScope::class)],
            'length' => 'required|numeric|min:1',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'service_length' => 'nullable|numeric|min:0',
            'tiers' => 'required|integer|min:1',
            'lines' => 'required|integer|min:1',
            'bird_weight_kg' => 'nullable|numeric|min:0',
            'side_fans_count' => 'nullable|integer|min:0',
            'heaters_count' => ['nullable', 'integer', Rule::in([0, 3, 4, 5, 6, 8])],
            'wall_type' => 'nullable|string',
            'vat_region' => ['nullable', 'string', Rule::in(['none', 'egypt', 'ksa'])],
        ]);

        $calc = PricingCalculator::fromSettings();
        $input = new QuotationInput(
            length: (float) $validated['length'],
            width: (float) $validated['width'],
            height: (float) $validated['height'],
            tiers: (int) $validated['tiers'],
            lines: (int) $validated['lines'],
            projectType: $validated['project_type'] ?? PoultryProjectType::Broiler->value,
            pricingScope: $validated['pricing_scope'] ?? PoultryPricingScope::FullProject->value,
            serviceLength: isset($validated['service_length']) ? (float) $validated['service_length'] : null,
            birdWeightKg: isset($validated['bird_weight_kg']) ? (float) $validated['bird_weight_kg'] : 2.1,
            sideFansCount: $validated['side_fans_count'] ?? null,
            heatersCount: isset($validated['heaters_count']) ? (int) $validated['heaters_count'] : 0,
            wallType: $validated['wall_type'] ?? null,
            vatRegion: $validated['vat_region'] ?? 'none',
        );

        $result = $calc->calculate($input);
        $grouped = [];
        foreach ($result->breakdown as $row) {
            $groupName = PoultrySectionLabels::groupLabel($row['section']);
            $grouped[$groupName][] = [
                'desc_ar' => $row['label_ar'],
                'qty' => $row['quantity'],
                'unit' => $row['unit'],
                'total_price' => $row['total'],
                'hide_unit_details' => $row['hide_unit_details'] ?? false,
            ];
        }

        // Compute display-group subtotals (3 groups)
        $displayGroupSubtotals = [];
        foreach ($result->sectionSubtotals as $section => $amount) {
            $groupName = PoultrySectionLabels::groupLabel($section);
            $displayGroupSubtotals[$groupName] = ($displayGroupSubtotals[$groupName] ?? 0) + (float) $amount;
        }

        $currency = $result->toArray()['currency'];

        $html = view('sales.quotations.pdf-preview', [
            'clientName' => $validated['client_name'],
            'clientPhone' => $validated['client_phone'] ?? null,
            'projectLabel' => PoultryProjectType::from($input->projectType)->labelAr(),
            'scopeLabel' => PoultryPricingScope::from($input->pricingScope)->labelAr(),
            'groupedItems' => $grouped,
            'groupSubtotals' => $displayGroupSubtotals,
            'subtotal' => (float) $result->subtotal,
            'vatAmount' => (float) $result->vatAmount,
            'total' => (float) $result->total,
            'totalUsd' => $currency['total_usd'],
            'usdRate' => $currency['rate'],
            'technical' => $result->technical,
        ])->render();

        $pdf = PDF::loadHTML($html, [
            'default_font' => 'cairo',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'custom_font_dir' => storage_path('fonts/'),
            'custom_font_data' => [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ]);

        $safeName = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $validated['client_name']);

        return $pdf->download("Quotation_{$safeName}_".date('Ymd_His').'.pdf');
    }
}
