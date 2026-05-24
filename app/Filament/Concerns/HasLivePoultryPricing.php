<?php

namespace App\Filament\Concerns;

use App\Enums\PoultryPricingScope;
use App\Models\QuotationSection;
use App\Services\Poultry\PoultryConfigLoader;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Services\PoultryHousePricingService;
use App\Support\BroilerWeightReference;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

trait HasLivePoultryPricing
{
    protected static function poultryPricingLiveCallback(bool $applyItems = false): \Closure
    {
        return function (Set $set, Get $get) use ($applyItems) {
            static::refreshLivePoultryPricing($set, $get, $applyItems);
        };
    }

    /**
     * Recompute technical + pricing preview and optionally sync quotation line items.
     */
    public static function refreshLivePoultryPricing(Set $set, Get $get, bool $applyItems = false): void
    {
        $length = (float) ($get('hall_length') ?? $get('length') ?? 0);
        $width = (float) ($get('hall_width') ?? $get('width') ?? 0);
        $height = (float) ($get('hall_height') ?? $get('height') ?? 0);
        $tiers = (int) ($get('tiers') ?? 0);
        $lines = (int) ($get('lines') ?? 0);

        if ($length <= 0 || $width <= 0 || $height <= 0 || $tiers <= 0 || $lines <= 0) {
            return;
        }

        try {
            $projectType = static::resolveProjectTypeFromForm($get);
            $serviceLength = (float) ($get('service_length') ?? $get('dead_zone_meters') ?? settings('poultry_pricing.default_service_length', 10));
            $birdWeight = (float) ($get('bird_weight_kg') ?? $get('average_weight_kg') ?? 2.1);

            if ($get('auto_lines_from_width') && $width > 0) {
                $calc = new PoultryTechnicalCalculator;
                $config = (new PoultryConfigLoader)->loadTechnicalConfig();
                $suggested = $calc->resolveLinesFromWidth($width, $config);
                if ($suggested > 0) {
                    $set('lines', $suggested);
                    $lines = $suggested;
                }
            }

            $input = [
                'project_type' => $projectType,
                'pricing_scope' => $get('pricing_scope') ?? PoultryPricingScope::FullProject->value,
                'hall_length' => $length,
                'hall_width' => $width,
                'hall_height' => $height,
                'service_length' => $serviceLength,
                'tiers' => $tiers,
                'lines' => $lines,
                'bird_weight_kg' => $birdWeight,
                'birds_per_nest' => $get('birds_per_nest'),
                'side_fans_count' => $get('side_fans_count') ?: null,
                'heaters_count' => $get('heaters_count') ?: null,
                'wall_type' => $get('wall_type'),
            ];

            $result = app(PoultryHousePricingService::class)->compute($input);
            $computed = $result['computed'];

            $set('pricing_preview', $result);
            $set('bird_capacity', $computed['bird_count']);
            $set('bird_count', $computed['bird_count']);
            $set('total_nests', $computed['total_nests'] ?? 0);
            $set('nests_per_line', $computed['nests_per_line'] ?? 0);
            $set('birds_per_nest', $result['technical']['birds_per_nest'] ?? null);
            $set('back_fans_count', $computed['back_fans_count']);
            $set('cooling_units', $computed['cooling_units']);
            $set('windows_count', $computed['windows_count']);
            $set('subtotal', $result['subtotal']);
            $set('dead_zone_meters', $serviceLength);

            if ($projectType === 'broiler') {
                $set('side_fans_count', $computed['side_fans_count']);
                $set('heaters_count', $computed['heaters_count']);
            }

            $shouldApplyItems = $applyItems || (bool) ($get('auto_apply_poultry_pricing') ?? true);
            if ($shouldApplyItems && ! empty($result['items'])) {
                $sectionMap = QuotationSection::pluck('id', 'category')->all();
                $items = collect($result['items'])->map(fn ($row, $idx) => [
                    'section_id' => $sectionMap[$row['section']] ?? null,
                    'description_ar' => $row['desc_ar'],
                    'description_en' => $row['desc_en'],
                    'unit' => $row['unit'],
                    'quantity' => (string) $row['qty'],
                    'unit_price' => (string) $row['unit_price'],
                    'discount_percentage' => '0',
                    'total_price' => (string) $row['total_price'],
                    'is_taxable' => true,
                    'sort_order' => $idx,
                    'notes' => null,
                ])->values()->toArray();

                $set('items', $items);
                $set('subtotal', (string) $result['subtotal']);

                if (method_exists(static::class, 'recalculateTotals')) {
                    static::recalculateTotals($set, $get, false);
                }
            }
        } catch (\Throwable $e) {
            $set('pricing_preview', ['error' => $e->getMessage()]);
        }
    }

    protected static function resolveProjectTypeFromForm(Get $get): string
    {
        if ($pt = $get('project_type')) {
            return $pt;
        }

        return match ($get('hall_type')) {
            'بياض', 'layer', 'LAYING' => 'layer',
            default => 'broiler',
        };
    }

    public static function broilerWeightTableSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('broiler_weight_table')
                ->label('جدول الوزن → طيور/عش (مع إجمالي الطيور)')
                ->columnSpanFull()
                ->content(function (Get $get) {
                    $preview = $get('pricing_preview');
                    $totalNests = (int) ($preview['computed']['total_nests'] ?? $get('total_nests') ?? 0);
                    $weight = (float) ($get('bird_weight_kg') ?? 2.1);

                    return BroilerWeightReference::htmlTable($weight, $totalNests > 0 ? $totalNests : null);
                })
                ->visible(fn (Get $get) => static::resolveProjectTypeFromForm($get) === 'broiler'),
        ];
    }

    public static function livePricingPreviewSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('live_preview_html')
                ->label('معاينة الحساب المباشر')
                ->columnSpanFull()
                ->content(function (Get $get) {
                    $preview = $get('pricing_preview');
                    if (! is_array($preview)) {
                        return new HtmlString('<p style="color:#64748b;font-size:13px;">أدخل الأبعاد لعرض الحساب التلقائي…</p>');
                    }
                    if (isset($preview['error'])) {
                        return new HtmlString('<p style="color:#dc2626;">'.e($preview['error']).'</p>');
                    }

                    $c = $preview['computed'] ?? [];
                    $subtotal = number_format((float) ($preview['subtotal'] ?? 0), 0);

                    $tech = $preview['technical'] ?? [];
                    $fanCount = $c['back_fans_count'] ?? 0;
                    $fanFormula = $tech['fan_formula'] ?? '';
                    $coolingFormula = $tech['cooling_formula'] ?? '';

                    $rows = [
                        ['الطول الفعال', e(($c['effective_length'] ?? '-').' م'), false],
                        ['أعشاش / خط', e(number_format($c['nests_per_line'] ?? 0)), false],
                        ['إجمالي الأعشاش', e(number_format($c['total_nests'] ?? 0)), false],
                        ['طيور / عش', e(number_format($tech['birds_per_nest'] ?? 0)), false],
                        ['عدد الطيور', e(number_format($c['bird_count'] ?? 0)), false],
                        ['الشفاطات', '<strong>'.e((string) $fanCount).'</strong>'
                            .($fanFormula ? '<br><span style="font-size:11px;color:#64748b">'.e($fanFormula).'</span>' : ''), true],
                        ['التبريد (م)', '<strong>'.e((string) ($c['cooling_units'] ?? 0)).'</strong>'
                            .($coolingFormula ? '<br><span style="font-size:11px;color:#64748b">'.e($coolingFormula).'</span>' : ''), true],
                        ['الشبابيك', e((string) ($c['windows_count'] ?? 0)), false],
                        ['المجموع الفرعي', e($subtotal.' ج.م'), false],
                    ];

                    if (! empty($preview['currency']['total_usd'])) {
                        $rows[] = ['بالدولار (تقريبي)', e(number_format($preview['currency']['total_usd'], 2).' $'), false];
                    }

                    $html = '<table style="width:100%;font-size:13px;border-collapse:collapse;">';
                    foreach ($rows as [$label, $val, $isHtml]) {
                        $cell = $isHtml ? $val : e((string) $val);
                        $html .= '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:6px 8px;color:#64748b;">'.e($label).'</td><td style="padding:6px 8px;font-weight:700;text-align:left;direction:ltr;">'.$cell.'</td></tr>';
                    }
                    $html .= '</table>';

                    return new HtmlString($html);
                }),
        ];
    }

    public static function poultryDimensionFieldsLive(bool $applyItemsOnChange = false): array
    {
        $cb = static::poultryPricingLiveCallback($applyItemsOnChange);

        return [
            'live' => true,
            'liveDebounce' => 400,
            'afterStateUpdated' => [$cb],
        ];
    }
}
