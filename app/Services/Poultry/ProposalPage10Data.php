<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use RuntimeException;

/**
 * Page 10 of the branded proposal: the financial offer.
 *
 * Money, rate, and brochure terms come from the quotation snapshot.
 * Amounts stay unrounded; the view rounds for display.
 */
class ProposalPage10Data
{
    /** @return array<string, mixed> */
    public function from(PoultryQuotation $quotation): array
    {
        $snapshot = $quotation->pricing_snapshot ?? [];
        $financial = is_array($snapshot['financial'] ?? null) ? $snapshot['financial'] : [];
        $terms = $this->terms($snapshot);

        $totalEgp = $this->money($financial, 'total');
        $subtotalEgp = $this->money($financial, 'subtotal');
        $vatEgp = $this->money($financial, 'vat_amount');
        $rate = $this->exchangeRate($snapshot);
        $barns = $this->barnsCount($quotation);

        $perBarnEgp = $totalEgp / $barns;
        $perBarnUsd = $perBarnEgp / $rate;
        $subtotalUsd = $subtotalEgp / $rate;
        $vatUsd = $vatEgp / $rate;
        $totalUsd = $totalEgp / $rate;

        $issued = $quotation->issued_at ?? $quotation->created_at;
        if ($issued === null) {
            throw new RuntimeException('تاريخ إصدار العرض غير موجود.');
        }

        $validityDays = max(0, (int) ($terms['validity_days'] ?? 0));
        $validityDate = $issued->copy()->addDays($validityDays)->format('d/m/Y');
        $projectType = (string) ($snapshot['project_type'] ?? $quotation->project_type ?? PoultryProjectType::Broiler->value);
        $batteryLabel = PoultryProjectType::tryFrom($projectType)?->labelAr() ?? 'تسمين';
        $unit = (string) ($terms['unit'] ?? 'داجن');
        $taxed = $vatEgp > 0 ? '14% شامل' : 'غير خاضع';

        $silo = $quotation->siloCapacity?->label_ar;
        $siloLabel = is_string($silo) && $silo !== '' ? $silo : '25 طن';
        $siloCount = (int) $quotation->silos_count;
        $siloLine = $siloCount > 1
            ? "+ عدد {$siloCount} سايلو سعة {$siloLabel} + بريمة مناولة سايلو\n"
            : "+ سايلو سعة {$siloLabel} + بريمة مناولة سايلو\n";

        $description = "توريد بطاريات {$batteryLabel} بالمواصفات السابقة\n"
            ."شاملة لوح الكنترول للأجزاء المذكورة أعلاه\n"
            .$siloLine
            .'توريد قطع غيار 1% من العنبر كخامات صاج وسلك';

        $zinc = (string) ($terms['zinc_coating_g_m2'] ?? '');
        $warranty = (string) ($terms['warranty_years'] ?? '');
        $includes = [];
        foreach ((array) ($terms['offer_includes'] ?? []) as $line) {
            $includes[] = str_replace([':zinc:', ':warranty:'], [$zinc, $warranty], (string) $line);
        }

        return [
            'primary' => (string) config('mi_proposal.primary_color', '#C00000'),
            'clientName' => $quotation->client_name ?: '—',
            'customerId' => $quotation->quote_number ?: '—',
            'date' => $issued->format('d/m/Y'),
            'validityDate' => $validityDate,
            'exchangeRate' => $rate,
            'rateUnit' => 'EGP per USD',
            'barnsCount' => $barns,
            'unit' => $unit,
            'taxed' => $taxed,
            'perBarnEgp' => $perBarnEgp,
            'perBarnUsd' => $perBarnUsd,
            'subtotalEgp' => $subtotalEgp,
            'subtotalUsd' => $subtotalUsd,
            'vatEgp' => $vatEgp,
            'vatUsd' => $vatUsd,
            'totalEgp' => $totalEgp,
            'totalUsd' => $totalUsd,
            'sections' => [
                [
                    'kind' => 'title',
                    'title' => 'البند المالي والفني لتجهيز بطاريات دواجن أوتوماتيك',
                ],
                [
                    'kind' => 'meta',
                    'customerId' => $quotation->quote_number ?: '—',
                    'date' => $issued->format('d/m/Y'),
                    'clientName' => $quotation->client_name ?: '—',
                ],
                [
                    'kind' => 'offer',
                    'description' => $description,
                    'unit' => $unit,
                    'taxed' => $taxed,
                    'line' => ['usd' => $perBarnUsd, 'egp' => $perBarnEgp],
                    'rows' => [
                        ['key' => 'subtotal', 'label' => 'Subtotal', 'usd' => $subtotalUsd, 'egp' => $subtotalEgp],
                        ['key' => 'vat', 'label' => 'VAT', 'usd' => $vatUsd, 'egp' => $vatEgp],
                        ['key' => 'total', 'label' => 'Total — '.$barns.' عنبر', 'usd' => $totalUsd, 'egp' => $totalEgp],
                    ],
                ],
                [
                    'kind' => 'rate',
                    'text' => '1 USD = '.number_format($rate, 2, '.', '').' EGP',
                    'date' => $issued->format('d/m/Y'),
                ],
                [
                    'kind' => 'validity',
                    'text' => 'ساري حتى '.$validityDate,
                ],
                [
                    'kind' => 'includes',
                    'title' => 'العرض شامل',
                    'items' => $includes,
                ],
            ],
        ];
    }

    public function stylesheet(string $primary): string
    {
        return <<<CSS
    .p10 { font-family: 'cairo', sans-serif; direction: rtl; color: #1a1a1a; }
    .p10 .title { background: {$primary}; color: #fff; text-align: center; font-weight: bold; font-size: 12pt; padding: 2.4mm 3mm; margin: 0 0 3.5mm 0; }
    .p10 .meta { width: 100%; border-collapse: collapse; margin-bottom: 3.5mm; }
    .p10 .meta td { font-size: 9.5pt; padding: 1mm 2mm; vertical-align: middle; }
    .p10 .meta .left { text-align: left; direction: ltr; color: {$primary}; font-weight: bold; width: 42%; }
    .p10 .meta .right { text-align: right; font-weight: bold; font-size: 11pt; width: 58%; }
    .p10 .fin { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
    .p10 .fin th { background: {$primary}; color: #fff; font-size: 9pt; font-weight: bold; padding: 2mm 1.5mm; border: 0.5pt solid {$primary}; text-align: center; }
    .p10 .fin td { border: 0.5pt solid #333; padding: 2.2mm 2mm; font-size: 8.8pt; vertical-align: top; }
    .p10 .fin .desc { text-align: right; width: 38%; line-height: 1.55; }
    .p10 .fin .c { text-align: center; white-space: nowrap; }
    .p10 .fin .amt { text-align: center; direction: ltr; font-weight: bold; white-space: nowrap; }
    .p10 .fin .sum td { background: #faf1f1; font-weight: bold; }
    .p10 .fin .grand td { background: #fff0f0; font-weight: bold; }
    .p10 .rate { font-size: 7.5pt; color: #555; text-align: left; direction: ltr; margin: 0 0 3.5mm 2mm; }
    .p10 .validity { text-align: center; color: {$primary}; font-weight: bold; font-size: 11pt; margin: 4mm 0; }
    .p10 .includes-title { color: {$primary}; font-weight: bold; font-size: 11pt; border-bottom: 1.2pt solid {$primary}; margin-bottom: 2mm; }
    .p10 .includes { margin: 0; padding: 0 5mm 0 0; list-style: none; }
    .p10 .includes li { font-size: 9.5pt; line-height: 1.75; padding: 0.3mm 0; }
    .p10 .hl { color: {$primary}; font-weight: bold; }
CSS;
    }

    /** @param  array<string, mixed>  $snapshot */
    private function terms(array $snapshot): array
    {
        $terms = $snapshot['terms'] ?? null;
        if (is_array($terms)) {
            return $terms;
        }

        return (new ProposalSnapshotFreezer)->termsFromConfig();
    }

    /** @param  array<string, mixed>  $financial */
    private function money(array $financial, string $key): float
    {
        if (! array_key_exists($key, $financial) || $financial[$key] === null || $financial[$key] === '') {
            throw new RuntimeException('البند المالي غير مكتمل: financial.'.$key.' غير موجود في لقطة العرض.');
        }

        return (float) $financial[$key];
    }

    /** @param  array<string, mixed>  $snapshot */
    private function exchangeRate(array $snapshot): float
    {
        $currency = is_array($snapshot['currency'] ?? null) ? $snapshot['currency'] : [];
        $parameters = is_array($snapshot['parameters'] ?? null) ? $snapshot['parameters'] : [];

        if (array_key_exists('rate', $currency) && $currency['rate'] !== null && $currency['rate'] !== '') {
            $rate = (float) $currency['rate'];
        } elseif (array_key_exists('egp_to_usd_rate', $parameters) && $parameters['egp_to_usd_rate'] !== null && $parameters['egp_to_usd_rate'] !== '') {
            $rate = (float) $parameters['egp_to_usd_rate'];
        } else {
            throw new RuntimeException('سعر الصرف غير موجود في لقطة العرض.');
        }

        if ($rate <= 1) {
            throw new RuntimeException('سعر الصرف يجب أن يكون بالجنيه المصري لكل دولار وأكبر من 1.');
        }

        return $rate;
    }

    private function barnsCount(PoultryQuotation $quotation): int
    {
        $raw = $quotation->barns_count;
        $numeric = is_numeric($raw) ? (float) $raw : -1;
        if ($numeric < 1 || (int) $numeric != $numeric) {
            throw new RuntimeException('عدد العنابر غير صالح في العرض.');
        }

        return (int) $numeric;
    }
}
