<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Support\SiloCapacity;

/**
 * Page 7 prints the quotation's silo capacity over the brochure's fixed "11 طن".
 */
class ProposalPage7Data
{
    public function stylesheet(): string
    {
        return <<<'CSS'
.p7 { font-family: 'cairo', sans-serif; direction: rtl; text-align: center; font-size: 13pt; line-height: 1.15; margin: 0; padding: 0; }
.p7 b { color: #ff0000; font-weight: bold; }
CSS;
    }

    /** @return array{silo: string} */
    public function from(PoultryQuotation $quotation): array
    {
        return [
            'silo' => $this->capacityLabel($quotation),
        ];
    }

    private function capacityLabel(PoultryQuotation $quotation): string
    {
        $selected = $quotation->siloCapacity?->label_ar;
        $selected = is_string($selected) ? $selected : null;
        $broiler = PoultryProjectType::tryFrom((string) $quotation->project_type)?->isBroiler() ?? false;

        if (! $broiler) {
            return $selected !== null && $selected !== '' ? $selected : '25 طن';
        }

        $birds = (int) $quotation->bird_count * max(1, (int) ($quotation->barns_count ?: 1));

        return SiloCapacity::labelForBirds($birds, $selected);
    }
}
