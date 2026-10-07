<?php

namespace App\Quotations\Templates;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Quotations\Contracts\QuotationTemplate;
use App\Quotations\Layers\LayersDocument;
use App\Quotations\Layers\LayersOriginalPages;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Throwable;
use Illuminate\Http\Response;

class LayersQuotationTemplate implements QuotationTemplate
{
    /** @var list<string> */
    private const PAYMENT_LABELS = [
        'عند التعاقد',
        'أثناء التركيب',
        'مع آخر سيارة',
    ];

    public function key(): string
    {
        return 'layers';
    }

    public function label(): string
    {
        return 'بطاريات بياض';
    }

    public function quoteTypeCode(): string
    {
        return 'batteries_layers_eg';
    }

    public function supports(string $projectType): bool
    {
        if ($projectType === PoultryProjectType::LayerRearing->value && ! config('quotations.layer_rearing_enabled')) {
            return false;
        }

        return (string) config('quotations.map.'.$projectType, 'broiler') === $this->key();
    }

    public function buildData(PoultryQuotation $q): array
    {
        $snapshot = is_array($q->pricing_snapshot) ? $q->pricing_snapshot : [];
        $technical = is_array($snapshot['technical'] ?? null) ? $snapshot['technical'] : [];
        $computed = is_array($snapshot['computed'] ?? null) ? $snapshot['computed'] : [];
        $financial = is_array($snapshot['financial'] ?? null) ? $snapshot['financial'] : [];

        $subtotal = $this->money($q->subtotal, $financial['subtotal'] ?? null);
        $vat = $this->money($q->vat_amount, $financial['vat_amount'] ?? null);
        $total = $this->money($q->total, $financial['total'] ?? null);
        $barns = max(1, (int) ($q->barns_count ?: 1));
        $rate = $this->exchangeRate($q, $snapshot);
        $projectType = (string) ($q->project_type ?? '');

        $payload = [
            'client_name' => $q->client_name,
            'issued_at' => $q->issued_at?->format('d/m/Y'),
            'quote_number' => $q->quote_number,
            'project_type' => $projectType,
            'project_type_label' => PoultryProjectType::tryFrom($projectType)?->labelAr(),
            'length' => $q->length,
            'width' => $q->width,
            'height' => $q->height,
            'location' => $q->client_location ?: $q->client_address,
            'effective_length' => $technical['effective_length'] ?? $computed['effective_length'] ?? null,
            'tiers' => $technical['tiers'] ?? $q->tiers,
            'lines' => $technical['lines'] ?? $q->lines,
            'nests_one_side' => $technical['nests_one_side'] ?? null,
            'nests_per_line' => $q->nests_per_line ?? $technical['nests_per_line'] ?? $computed['nests_per_line'] ?? null,
            'total_nests' => $q->total_nests ?? $technical['total_nests'] ?? $computed['total_nests'] ?? null,
            'birds_per_nest' => $q->birds_per_nest ?? $technical['birds_per_nest'] ?? null,
            'bird_count' => $q->bird_count ?? $technical['total_birds'] ?? $computed['bird_count'] ?? null,
            'barns_count' => $barns,
            'silo_capacity' => $this->lookupLabel($q, 'siloCapacity'),
            'inner_belt' => $this->lookupLabel($q, 'innerBeltLength'),
            'outer_belt' => $this->lookupLabel($q, 'outerBeltLength'),
            'motor_power' => $this->lookupLabel($q, 'motorPower'),
            'manure_motor_count' => $this->lookupLabel($q, 'manureMotorCount'),
            'belts_per_line' => $this->lookupLabel($q, 'beltsPerLine'),
            'aisle_cm' => $this->optionalNumber($technical['aisle_cm'] ?? null),
            'battery_height_m' => $this->optionalNumber($technical['battery_height_m'] ?? null),
            'cage_height_cm' => $this->optionalNumber($technical['cage_height_cm'] ?? null),
            'cage_area_cm2' => $this->optionalNumber($technical['cage_area_cm2'] ?? null),
            'supply_days' => $this->optionalNumber($technical['supply_days'] ?? null),
            'validity_days' => (int) config('quotations.layers.validity_days', 3),
            'validity_date' => $q->issued_at
                ? $q->issued_at->copy()->addDays((int) config('quotations.layers.validity_days', 3))->format('d/m/Y')
                : null,
            'exchange_rate' => $rate,
            'financial' => [
                'subtotal' => $subtotal,
                'vat_amount' => $vat,
                'total' => $total,
                'barn_egp' => round($total / $barns, 2),
                'barn_usd' => $rate > 0 ? round(($total / $barns) / $rate, 2) : null,
            ],
            'payments' => $this->payments($total, $rate),
            'spare_parts' => [
                'percent' => (float) config('quotations.spare_parts_percent', 1),
                'descriptive_only' => true,
            ],
            'stocking' => $this->stocking($q, $technical),
        ];

        if ($payload['cage_area_cm2'] === null && $payload['stocking']['width_cm'] !== null && $payload['stocking']['depth_cm'] !== null) {
            $payload['cage_area_cm2'] = round($payload['stocking']['width_cm'] * $payload['stocking']['depth_cm'], 2);
        }

        $payload['document'] = (new LayersDocument)->present($payload);

        return $payload;
    }

    public function render(PoultryQuotation $q): Response
    {
        $number = $q->quote_number ?: 'quotation';

        return response($this->pdfBytes($q), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="MI-Layers-'.$number.'.pdf"',
        ]);
    }

    public function pdfBytes(PoultryQuotation $q): string
    {
        $pages = new LayersOriginalPages;
        $docx = null;

        try {
            $docx = $pages->fill($q);

            return $pages->exportPdf($docx);
        } catch (Throwable $e) {
            if (PHP_OS_FAMILY === 'Windows') {
                throw $e;
            }

            report($e);

            return $this->htmlPdf($q);
        } finally {
            if (is_string($docx)) {
                @unlink($docx);
            }
        }
    }

    /**
     * مسار السيرفر: Word غير متاح على لينكس، فيُرسم العرض بـ mPDF.
     */
    private function htmlPdf(PoultryQuotation $q): string
    {
        $document = $this->buildData($q)['document'];
        $styles = view('quotations.layers.styles', $document)->render();
        $cover = view('quotations.layers.cover', $document)->render();
        $body = view('quotations.layers.body', $document)->render();
        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><style>'
            .$styles
            .'</style></head><body>'
            .$cover
            .$body
            .'</body></html>';

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');
        $pdf = PDF::loadHTML($html, [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'cairo',
            'default_font_size' => 11,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 18,
            'margin_bottom' => 16,
            'margin_header' => 6,
            'margin_footer' => 6,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'tempDir' => $tempDir,
            'custom_font_dir' => $fontDir,
            'custom_font_data' => [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ]);

        return $pdf->output();
    }

    public function shareCardData(PoultryQuotation $q): array
    {
        $data = $this->buildData($q);

        return [
            'template' => $this->key(),
            'quote_number' => $data['quote_number'],
            'client_name' => $data['client_name'],
            'project_type' => $data['project_type'],
            'total' => $data['financial']['total'],
            'currency' => 'EGP',
        ];
    }

    /** @param  array<string, mixed>  $technical
     * @return array<string, mixed>
     */
    private function stocking(PoultryQuotation $q, array $technical): array
    {
        $width = $this->optionalNumber($technical['nest_width_cm'] ?? null);
        if ($width === null && isset($technical['layer_nest_module_m']) && is_numeric($technical['layer_nest_module_m']) && (float) $technical['layer_nest_module_m'] > 0) {
            $width = round((float) $technical['layer_nest_module_m'] * 100, 2);
        }

        $depth = $this->optionalNumber($technical['nest_depth_cm'] ?? $technical['cage_depth_cm'] ?? null);
        $birds = $this->optionalNumber($q->birds_per_nest ?? $technical['birds_per_nest'] ?? null);
        $area = ($width !== null && $depth !== null && $birds !== null && $birds > 0)
            ? round($width * $depth / $birds, 2)
            : null;
        $feeding = ($width !== null && $birds !== null && $birds > 0)
            ? round($width / $birds, 2)
            : null;

        return [
            'area_cm2' => $area,
            'feeding_cm' => $feeding,
            'width_cm' => $width,
            'depth_cm' => $depth,
            'birds' => $birds,
            'area_formula' => 'nest_width_cm * nest_depth_cm / birds_per_nest',
            'feeding_formula' => 'nest_width_cm / birds_per_nest',
        ];
    }

    private function optionalNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        return (float) $value;
    }

    private function lookupLabel(PoultryQuotation $q, string $relation): ?string
    {
        $label = $q->{$relation}?->label_ar;

        return is_string($label) && $label !== '' ? $label : null;
    }

    /** @param  array<string, mixed>  $snapshot */
    private function exchangeRate(PoultryQuotation $q, array $snapshot): ?float
    {
        if ($q->exchange_rate !== null && (float) $q->exchange_rate > 0) {
            return (float) $q->exchange_rate;
        }

        $currency = is_array($snapshot['currency'] ?? null) ? $snapshot['currency'] : [];
        if (isset($currency['rate']) && (float) $currency['rate'] > 0) {
            return (float) $currency['rate'];
        }

        return null;
    }

    private function money(mixed $column, mixed $snapshot): float
    {
        if ($column !== null && $column !== '') {
            return (float) $column;
        }

        return (float) ($snapshot ?? 0);
    }

    /** @return list<array{percent: int, label: ?string, egp: float, usd: ?float}> */
    private function payments(float $totalEgp, ?float $rate): array
    {
        $percents = array_values((array) config('quotations.payment_schedule', [70, 25, 5]));
        $egpParts = $this->split($totalEgp, $percents);
        $usdTotal = $rate !== null && $rate > 0 ? round($totalEgp / $rate, 2) : null;
        $usdParts = $usdTotal === null ? [] : $this->split($usdTotal, $percents);
        $lines = [];

        foreach ($egpParts as $index => $part) {
            $lines[] = [
                'percent' => $part['percent'],
                'label' => self::PAYMENT_LABELS[$index] ?? null,
                'egp' => $part['amount'],
                'usd' => $usdParts[$index]['amount'] ?? null,
            ];
        }

        return $lines;
    }

    /**
     * فرق التقريب يذهب للدفعة الأخيرة حتى يساوي المجموع المبلغ بالضبط.
     *
     * @param  list<int|float|string>  $percents
     * @return list<array{percent: int, amount: float}>
     */
    private function split(float $amount, array $percents): array
    {
        if ($percents === []) {
            return [];
        }

        $totalCents = (int) round($amount * 100);
        $allocated = 0;
        $lastIndex = count($percents) - 1;
        $parts = [];

        foreach ($percents as $index => $percent) {
            if ($index === $lastIndex) {
                $cents = $totalCents - $allocated;
            } else {
                $cents = (int) round($totalCents * ((float) $percent) / 100);
                $allocated += $cents;
            }

            $parts[] = [
                'percent' => (int) $percent,
                'amount' => $cents / 100,
            ];
        }

        return $parts;
    }
}
