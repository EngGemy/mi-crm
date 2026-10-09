<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;

/**
 * Page 2 of the branded proposal: the barn card on the cover photo.
 *
 * Hall size and project type come from the quotation snapshot. The client
 * name and site are saved on the quotation record.
 */
class ProposalPage2Data
{
    /** @return array<string, mixed> */
    public function from(PoultryQuotation $quotation): array
    {
        $snapshot = $quotation->pricing_snapshot ?? [];
        $inputs = is_array($snapshot['inputs'] ?? null) ? $snapshot['inputs'] : [];
        $projectType = (string) ($snapshot['project_type'] ?? $inputs['project_type'] ?? $quotation->project_type ?? PoultryProjectType::Broiler->value);

        $length = $this->dimension($inputs, 'hall_length', $quotation->length);
        $width = $this->dimension($inputs, 'hall_width', $quotation->width);
        $height = $this->dimension($inputs, 'hall_height', $quotation->height);
        $location = trim((string) ($quotation->client_location ?: $quotation->client_address ?: ''));

        return [
            'primary' => (string) config('mi_proposal.primary_color', '#C00000'),
            'clientName' => $quotation->client_name ?: '—',
            'projectType' => $this->projectLabel($projectType),
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'location' => $location !== '' ? $location : '—',
            'sections' => [
                [
                    'kind' => 'title',
                    'title' => 'عرض مالي وفني بطاريات دواجن أوتوماتيك',
                ],
                [
                    'kind' => 'intro',
                    'text' => 'مقدم الى '.($quotation->client_name ?: '—'),
                ],
                [
                    'kind' => 'barn',
                    'title' => 'تفاصيل العنبر',
                    'rows' => [
                        ['label' => 'نوع العنبر', 'value' => $this->projectLabel($projectType)],
                        ['label' => 'طول العنبر', 'value' => $this->meters($length)],
                        ['label' => 'عرض العنبر', 'value' => $this->meters($width)],
                        ['label' => 'ارتفاع العنبر', 'value' => $this->meters($height)],
                        ['label' => 'مكان المشروع', 'value' => $location !== '' ? $location : '—'],
                        ['label' => 'الأعمدة الداخلية', 'value' => (string) (int) ($quotation->internal_columns ?? 0)],
                        ...$this->roofRow($quotation, $projectType),
                    ],
                ],
            ],
        ];
    }

    public function stylesheet(string $primary): string
    {
        return <<<CSS
    .p2 { font-family: 'cairo', sans-serif; direction: rtl; color: #1a1a1a; }
    .p2 .title { background: {$primary}; color: #fff; text-align: center; font-weight: bold; font-size: 12pt; padding: 2.6mm 3mm; margin: 0 0 2.4mm 0; }
    .p2 .intro { text-align: center; font-weight: bold; font-size: 11pt; margin: 0 0 2.6mm 0; }
    .p2 table { width: 100%; border-collapse: collapse; }
    .p2 .bar td { background: {$primary}; color: #fff; font-weight: bold; font-size: 10.5pt; text-align: center; padding: 2.2mm 0; border: 0.4pt solid {$primary}; }
    .p2 .r td { border: 0.4pt solid #e0b4b4; padding: 2.5mm 3mm; font-size: 10pt; }
    .p2 .lbl { width: 55%; text-align: right; font-weight: bold; }
    .p2 .val { width: 45%; text-align: center; font-weight: bold; }
    .p2 .odd td { background: #faf1f1; }
    .p2 .even td { background: #ffffff; }
CSS;
    }

    /** @return list<array{label: string, value: string}> */
    private function roofRow(PoultryQuotation $quotation, string $projectType): array
    {
        if (! (PoultryProjectType::tryFrom($projectType)?->isBroiler() ?? false)) {
            return [];
        }

        $label = match ($quotation->roof_type) {
            'flat' => 'مستوى',
            'gable' => 'جمالون',
            default => '—',
        };

        return [['label' => 'نوع السقف', 'value' => $label]];
    }

    private function projectLabel(string $projectType): string
    {
        return PoultryProjectType::tryFrom($projectType)?->labelAr()
            ?? ($projectType !== '' ? $projectType : '—');
    }

    /** @param  array<string, mixed>  $inputs */
    private function dimension(array $inputs, string $key, mixed $fallback): float
    {
        $value = $inputs[$key] ?? $fallback;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function meters(float $n): string
    {
        if ($n <= 0) {
            return '—';
        }

        $formatted = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        return ($formatted === '' ? '0' : $formatted).' متر';
    }
}
