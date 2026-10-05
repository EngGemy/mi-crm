<?php

namespace App\Services\Poultry;

use App\Models\PoultryQuotation;

/**
 * Freezes brochure terms and a manual exchange-rate override onto the snapshot.
 *
 * Terms are written once. The rate override is written only when the saved
 * exchange rate differs from the rate already on the snapshot.
 */
class ProposalSnapshotFreezer
{
    public function apply(PoultryQuotation $quotation): void
    {
        $snapshot = $quotation->pricing_snapshot ?? [];
        if ($snapshot === []) {
            return;
        }

        if (! isset($snapshot['terms']) || ! is_array($snapshot['terms'])) {
            $snapshot['terms'] = $this->termsFromConfig();
        }

        $manual = $quotation->exchange_rate;
        if ($manual !== null && $manual !== '' && (float) $manual > 1) {
            $currency = is_array($snapshot['currency'] ?? null) ? $snapshot['currency'] : [];
            $current = (float) ($currency['rate'] ?? 0);
            if (abs((float) $manual - $current) > 0.0000001) {
                $currency['rate'] = (float) $manual;
                $currency['rate_override'] = [
                    'user_id' => auth()->id(),
                    'at' => now()->toIso8601String(),
                ];
                $snapshot['currency'] = $currency;
            }
        }

        $quotation->pricing_snapshot = $snapshot;
    }

    /** @return array{validity_days: int, zinc_coating_g_m2: int, warranty_years: int, offer_includes: list<string>, unit: string} */
    public function termsFromConfig(): array
    {
        return [
            'validity_days' => (int) config('mi_proposal.validity_days', 3),
            'zinc_coating_g_m2' => (int) config('mi_proposal.specs.zinc_coating_g_m2', 275),
            'warranty_years' => (int) config('mi_proposal.warranty_years', 12),
            'offer_includes' => array_values((array) config('mi_proposal.offer_includes', [])),
            'unit' => (string) config('mi_proposal.unit', 'داجن'),
        ];
    }
}
