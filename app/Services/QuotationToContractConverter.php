<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Quotation;

/**
 * @deprecated Use UnifiedQuotationToContractConverter directly
 */
class QuotationToContractConverter
{
    public function convert(Quotation $quotation, array $additionalData = []): Contract
    {
        return app(UnifiedQuotationToContractConverter::class)->convertQuotation($quotation, $additionalData);
    }
}
