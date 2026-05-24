<?php

namespace App\Http\Controllers\Api;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Http\Controllers\Controller;
use App\Services\Pricing\DTOs\QuotationInput;
use App\Services\Pricing\PricingCalculator;
use App\Support\BroilerWeightReference;
use App\Support\PoultrySectionLabels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PoultryPricingController extends Controller
{
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_type' => ['nullable', Rule::enum(PoultryProjectType::class)],
            'pricing_scope' => ['nullable', Rule::enum(PoultryPricingScope::class)],
            'length' => 'required_without:hall_length|numeric|min:1',
            'hall_length' => 'required_without:length|numeric|min:1',
            'width' => 'required_without:hall_width|numeric|min:1',
            'hall_width' => 'required_without:width|numeric|min:1',
            'height' => 'required_without:hall_height|numeric|min:1',
            'hall_height' => 'required_without:height|numeric|min:1',
            'service_length' => 'nullable|numeric|min:0',
            'tiers' => 'required|integer|min:1',
            'lines' => 'required|integer|min:1',
            'bird_weight_kg' => 'nullable|numeric|min:0',
            'birds_per_nest' => 'nullable|integer|min:1',
            'side_fans_count' => 'nullable|integer|min:0',
            'heaters_count' => 'nullable|integer|min:0',
            'wall_type' => 'nullable|string',
            'vat_region' => ['nullable', 'string', Rule::in(['none', 'egypt', 'ksa'])],
        ]);

        $length = (float) ($validated['length'] ?? $validated['hall_length']);
        $width = (float) ($validated['width'] ?? $validated['hall_width']);
        $height = (float) ($validated['height'] ?? $validated['hall_height']);

        $hallType = $request->input('hall_type');
        $projectType = $validated['project_type'] ?? match ($hallType) {
            'بياض', 'layer' => PoultryProjectType::Layer->value,
            default => PoultryProjectType::Broiler->value,
        };

        $calc = PricingCalculator::fromSettings();

        $input = new QuotationInput(
            length: $length,
            width: $width,
            height: $height,
            tiers: (int) $validated['tiers'],
            lines: (int) $validated['lines'],
            projectType: $projectType,
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

        $totalNests = $result->totalNests;
        $birdsPerNest = (int) ($result->technical['birds_per_nest'] ?? 0);

        $technical = $result->technical ?? [];

        return response()->json(array_merge($result->toArray(), [
            'section_subtotals' => $result->sectionSubtotals,
            'effective_length' => $result->effectiveLength,
            'nests_per_line' => $result->nestsPerLine,
            'total_nests' => $totalNests,
            'birds_per_nest' => $birdsPerNest,
            'fan_load_kg' => $technical['fan_load_kg'] ?? null,
            'ventilation_max_weight_kg' => $technical['ventilation_max_weight_kg'] ?? null,
            'fan_capacity_kg' => $technical['fan_capacity_kg'] ?? 5000,
            'fan_formula' => $technical['fan_formula'] ?? null,
            'cooling_formula' => $technical['cooling_formula'] ?? null,
            'section_labels' => PoultrySectionLabels::labelsAr(),
            'weight_table' => array_map(fn ($row) => [
                'weight_kg' => $row['weight_kg'],
                'birds_per_nest' => $row['birds_per_nest'],
                'total_birds' => $totalNests > 0 ? $totalNests * $row['birds_per_nest'] : null,
            ], BroilerWeightReference::rows()),
        ]));
    }
}
