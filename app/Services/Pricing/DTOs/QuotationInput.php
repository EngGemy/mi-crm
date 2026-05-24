<?php

namespace App\Services\Pricing\DTOs;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use InvalidArgumentException;

readonly class QuotationInput
{
    public function __construct(
        public float $length,
        public float $width,
        public float $height,
        public int $tiers,
        public int $lines,
        public string $projectType = PoultryProjectType::Broiler->value,
        public string $pricingScope = PoultryPricingScope::FullProject->value,
        public ?float $serviceLength = null,
        public ?float $birdWeightKg = 2.1,
        public ?int $birdsPerNest = null,
        public ?int $sideFansCount = null,
        public ?int $heatersCount = null,
        public ?string $wallType = null,
        public ?array $customItemKeys = null,
        public string $vatRegion = 'none',
    ) {
        if ($this->length <= 0) {
            throw new InvalidArgumentException('Length must be greater than zero');
        }
        if ($this->width <= 0) {
            throw new InvalidArgumentException('Width must be greater than zero');
        }
        if ($this->height <= 0) {
            throw new InvalidArgumentException('Height must be greater than zero');
        }
        if ($this->tiers <= 0) {
            throw new InvalidArgumentException('Tiers must be greater than zero');
        }
        if ($this->lines <= 0) {
            throw new InvalidArgumentException('Lines must be greater than zero');
        }
        if (! in_array($this->vatRegion, ['none', 'egypt', 'ksa'], true)) {
            throw new InvalidArgumentException('VAT region must be none, egypt, or ksa');
        }
        PoultryProjectType::from($this->projectType);
        PoultryPricingScope::from($this->pricingScope);
    }

    public function toArray(): array
    {
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'tiers' => $this->tiers,
            'lines' => $this->lines,
            'project_type' => $this->projectType,
            'pricing_scope' => $this->pricingScope,
            'service_length' => $this->serviceLength,
            'bird_weight_kg' => $this->birdWeightKg,
            'birds_per_nest' => $this->birdsPerNest,
            'side_fans_count' => $this->sideFansCount,
            'heaters_count' => $this->heatersCount,
            'wall_type' => $this->wallType,
            'custom_item_keys' => $this->customItemKeys,
            'vat_region' => $this->vatRegion,
        ];
    }
}
