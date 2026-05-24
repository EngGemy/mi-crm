<?php

namespace App\Services\Pricing\DTOs;

use App\Support\CurrencyConverter;

readonly class QuotationResult
{
    public function __construct(
        public int $birdCount,
        public float $effectiveLength,
        public int $backFansCount,
        public float $coolingUnits,
        public int $windowsCount,
        public string $concreteCost,
        public string $steelCost,
        public string $wallsCost,
        public string $tanksCost,
        public string $batteryCost,
        public string $backFansCost,
        public string $coolingCost,
        public string $windowsCost,
        public string $sideFansCost,
        public string $heatersCost,
        public string $controlCost,
        public string $subtotal,
        public string $vatAmount,
        public string $total,
        public array $breakdown = [],
        public array $technical = [],
        public array $sectionSubtotals = [],
        public int $totalNests = 0,
        public int $nestsPerLine = 0,
        public int $sideFansCount = 0,
        public int $heatersCount = 0,
        public string $projectType = 'broiler',
        public string $pricingScope = 'full_project',
    ) {}

    public function toArray(): array
    {
        return [
            'bird_count' => $this->birdCount,
            'effective_length' => $this->effectiveLength,
            'back_fans_count' => $this->backFansCount,
            'cooling_units' => $this->coolingUnits,
            'windows_count' => $this->windowsCount,
            'total_nests' => $this->totalNests,
            'nests_per_line' => $this->nestsPerLine,
            'side_fans_count' => $this->sideFansCount,
            'heaters_count' => $this->heatersCount,
            'project_type' => $this->projectType,
            'pricing_scope' => $this->pricingScope,
            'technical' => $this->technical,
            'section_subtotals' => $this->sectionSubtotals,
            'concrete_cost' => $this->concreteCost,
            'steel_cost' => $this->steelCost,
            'walls_cost' => $this->wallsCost,
            'tanks_cost' => $this->tanksCost,
            'battery_cost' => $this->batteryCost,
            'back_fans_cost' => $this->backFansCost,
            'cooling_cost' => $this->coolingCost,
            'windows_cost' => $this->windowsCost,
            'side_fans_cost' => $this->sideFansCost,
            'heaters_cost' => $this->heatersCost,
            'control_cost' => $this->controlCost,
            'subtotal' => $this->subtotal,
            'vat_amount' => $this->vatAmount,
            'total' => $this->total,
            'breakdown' => $this->breakdown,
            'currency' => CurrencyConverter::quotationTotals(
                (float) $this->subtotal,
                (float) $this->vatAmount,
                (float) $this->total
            ),
        ];
    }
}
