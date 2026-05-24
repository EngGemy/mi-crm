<?php

namespace App\Services\Pricing;

use App\Services\PoultryHousePricingService;
use App\Services\Pricing\DTOs\QuotationInput;
use App\Services\Pricing\DTOs\QuotationResult;
use App\Support\FinancialEngine;
use App\Support\TaxResolver;

/**
 * Thin adapter over PoultryHousePricingService — single pricing authority.
 */
class PricingCalculator
{
    public function __construct(private array $params = []) {}

    public function calculate(QuotationInput $input): QuotationResult
    {
        $service = new PoultryHousePricingService;
        $mergedParams = ! empty($this->params)
            ? $this->params
            : $service->loadParams($input->wallType);

        $computeInput = [
            'project_type' => $input->projectType,
            'pricing_scope' => $input->pricingScope,
            'hall_length' => $input->length,
            'hall_width' => $input->width,
            'hall_height' => $input->height,
            'service_length' => $input->serviceLength,
            'tiers' => $input->tiers,
            'lines' => $input->lines,
            'bird_weight_kg' => $input->birdWeightKg,
            'birds_per_nest' => $input->birdsPerNest,
            'side_fans_count' => $input->sideFansCount,
            'heaters_count' => $input->heatersCount,
            'wall_type' => $input->wallType,
            'custom_item_keys' => $input->customItemKeys,
        ];

        $result = $service->compute($computeInput, $mergedParams);
        $computed = $result['computed'];
        $itemsByKey = collect($result['items'])->keyBy('key');

        $getCost = fn (string $key): string => number_format((float) ($itemsByKey[$key]['total_price'] ?? 0), 2, '.', '');

        $subtotal = number_format((float) $result['subtotal'], 2, '.', '');

        $vatPercentage = TaxResolver::percentageFor($input->vatRegion);
        $financial = FinancialEngine::calculateTotals((float) $subtotal, 0, 0, $vatPercentage);

        $vatAmount = $financial['vat_amount'];
        $total = $financial['total'];

        $breakdown = array_map(fn ($row) => [
            'key' => $row['key'],
            'label_ar' => $row['desc_ar'],
            'label_en' => $row['desc_en'],
            'quantity' => $row['qty'],
            'unit' => $row['unit'],
            'unit_price' => $row['unit_price'],
            'total' => $row['total_price'],
            'section' => $row['section'],
            'hide_unit_details' => $row['hide_unit_details'] ?? false,
        ], $result['items']);

        return new QuotationResult(
            birdCount: (int) $computed['bird_count'],
            effectiveLength: (float) $computed['effective_length'],
            backFansCount: (int) $computed['back_fans_count'],
            coolingUnits: (float) $computed['cooling_units'],
            windowsCount: (int) $computed['windows_count'],
            concreteCost: $getCost('concrete'),
            steelCost: $getCost('steel'),
            wallsCost: $getCost('walls'),
            tanksCost: $getCost('tanks'),
            batteryCost: $getCost('battery'),
            backFansCost: $getCost('main_fans'),
            coolingCost: $getCost('cooling'),
            windowsCost: $getCost('windows'),
            sideFansCost: $getCost('side_fans'),
            heatersCost: $getCost('heaters'),
            controlCost: $getCost('control'),
            subtotal: $subtotal,
            vatAmount: $vatAmount,
            total: $total,
            breakdown: $breakdown,
            technical: $result['technical'],
            sectionSubtotals: $result['section_subtotals'],
            totalNests: (int) ($computed['total_nests'] ?? 0),
            nestsPerLine: (int) ($computed['nests_per_line'] ?? 0),
            sideFansCount: (int) ($computed['side_fans_count'] ?? 0),
            heatersCount: (int) ($computed['heaters_count'] ?? 0),
            projectType: $input->projectType,
            pricingScope: $input->pricingScope,
        );
    }

    public function getBreakdown(QuotationInput $input): array
    {
        return $this->calculate($input)->breakdown;
    }

    /** @deprecated Use PoultryHousePricingService directly */
    public static function fromSettings(): self
    {
        return new self((new PoultryHousePricingService)->loadParams());
    }
}
