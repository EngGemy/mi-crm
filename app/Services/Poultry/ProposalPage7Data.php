<?php

namespace App\Services\Poultry;

use App\Models\PoultryQuotation;

/**
 * Page 7 prints the quotation's silo capacity over the brochure's fixed "11 طن".
 */
class ProposalPage7Data
{
    public function stylesheet(): string
    {
        return <<<'CSS'
.p7 { font-family: xbriyaz, sans-serif; direction: rtl; text-align: center; font-size: 13pt; line-height: 1.15; margin: 0; padding: 0; }
.p7 b { color: #ff0000; font-weight: bold; }
CSS;
    }

    /** @return array{silo: string} */
    public function from(PoultryQuotation $quotation): array
    {
        $label = $quotation->siloCapacity?->label_ar;

        return [
            'silo' => is_string($label) && $label !== '' ? $label : '25 طن',
        ];
    }
}
