<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\PoultryQuotation;

/**
 * @deprecated Use UnifiedQuotationToContractConverter directly
 */
class PoultryQuotationToContractConverter
{
    public function convert(PoultryQuotation $quotation, array $additionalData = []): Contract
    {
        return app(UnifiedQuotationToContractConverter::class)->convertPoultryQuotation($quotation, $additionalData);
    }
}
