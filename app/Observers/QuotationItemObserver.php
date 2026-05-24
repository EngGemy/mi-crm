<?php

namespace App\Observers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\QuotationCalculator;

class QuotationItemObserver
{
    public function saving(QuotationItem $item): void
    {
        $item->total_price = QuotationCalculator::formatDecimalString(
            QuotationCalculator::calculateItemTotal([
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'discount_percentage' => $item->discount_percentage ?? 0,
            ])
        );
    }

    public function saved(QuotationItem $item): void
    {
        $quotation = $item->quotation;
        if ($quotation) {
            QuotationCalculator::applyQuotationHeaderTotals($quotation->fresh(['items']));
        }
    }

    public function deleted(QuotationItem $item): void
    {
        $quotationId = $item->quotation_id;
        if (! $quotationId) {
            return;
        }

        $quotation = Quotation::query()->find($quotationId);
        if ($quotation) {
            QuotationCalculator::applyQuotationHeaderTotals($quotation->fresh(['items']));
        }
    }
}
