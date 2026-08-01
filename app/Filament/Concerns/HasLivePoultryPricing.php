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
    protected const LIVE_DEBOUNCE_MS = 800;

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
                'include_monitor' => (bool) ($get('include_monitor') ?? false),
                'monitor_cost' => $get('monitor_cost'),
                'include_electricity' => (bool) ($get('include_electricity') ?? false),
                'electricity_cost' => $get('electricity_cost'),
            ];

            $result = app(PoultryHousePricingService::class)->compute($input);
            $computed = $result['computed'];

            $set('pricing_preview', $result);
            $set('bird_capacity', $computed['bird_count']);
            $set('bird_count', $computed['bird_count']);
            $set('total_nests', $computed['total_nests'] ?? 0);
            $set('nests_per_line', $computed['nests_per_line'] ?? 0);
            $set('birds_per_nest', $result['technical']['birds_per_nest'] ?? null);
            $set('subtotal', $result['subtotal']);
            $set('dead_zone_meters', $serviceLength);

            if ($projectType === 'broiler') {
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

    protected static function pricingScopeFromForm(Get $get): string
    {
        return $get('pricing_scope') ?? PoultryPricingScope::FullProject->value;
    }

    protected static function showsBatteryPreview(Get $get): bool
    {
        return in_array(static::pricingScopeFromForm($get), [
            PoultryPricingScope::FullProject->value,
            PoultryPricingScope::BatteriesOnly->value,
            PoultryPricingScope::BatteriesAndAccessories->value,
            PoultryPricingScope::Custom->value,
        ], true);
    }

    protected static function showsAccessoriesPreview(Get $get): bool
    {
        return in_array(static::pricingScopeFromForm($get), [
            PoultryPricingScope::FullProject->value,
            PoultryPricingScope::AccessoriesOnly->value,
            PoultryPricingScope::BatteriesAndAccessories->value,
            PoultryPricingScope::Custom->value,
        ], true);
    }

    protected static function showsWallTypeField(Get $get): bool
    {
        return ! in_array(static::pricingScopeFromForm($get), [
            PoultryPricingScope::AccessoriesOnly->value,
            PoultryPricingScope::BatteriesAndAccessories->value,
        ], true);
    }

    public static function broilerWeightTableSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('broiler_weight_table')
                ->label('جدول الوزن → طيور/عش (مع إجمالي الطيور)')
                ->columnSpanFull()
                ->content(function (Get $get) {
                    $preview = $get('pricing_preview');
                    $totalNests = (int) (
                        $preview['computed']['total_nests']
                        ?? $preview['technical']['total_nests']
                        ?? $get('total_nests')
                        ?? 0
                    );
                    $weight = (float) ($get('bird_weight_kg') ?? $get('average_weight_kg') ?? 2.1);

                    // إن لم يُحسب بعد — قدّر من الطول الفعّال إن توفر في المعاينة
                    if ($totalNests <= 0 && is_array($preview) && ! isset($preview['error'])) {
                        $totalNests = (int) ($preview['computed']['total_nests'] ?? 0);
                    }

                    return BroilerWeightReference::htmlTable($weight, $totalNests > 0 ? $totalNests : null);
                })
                ->visible(fn (Get $get) => (
                    static::showsBatteryPreview($get)
                    || filled($get('bird_weight_kg'))
                    || filled($get('hall_type'))
                ) && static::resolveProjectTypeFromForm($get) === 'broiler'),
        ];
    }

    public static function accessoriesPreviewTableSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('accessories_preview_table')
                ->label('جدول المشتملات')
                ->columnSpanFull()
                ->content(function (Get $get) {
                    $preview = $get('pricing_preview');
                    if (! is_array($preview) || isset($preview['error'])) {
                        return new HtmlString('<p style="color:#64748b;font-size:13px;">أدخل الأبعاد لحساب المشتملات…</p>');
                    }

                    $rows = [];

                    $items = collect($preview['items'] ?? []);
                    $heaterItem = $items->firstWhere('key', 'heaters');
                    if ($heaterItem && (float) ($heaterItem['qty'] ?? 0) > 0) {
                        $rows[] = [
                            'الدفايات',
                            '<strong>'.e(number_format((float) ($heaterItem['qty'] ?? 0), 0)).'</strong>'
                            .' — '.e(number_format((float) ($heaterItem['total_price'] ?? 0), 0)).' ج.م',
                            true,
                        ];
                    }
                    $monitorItem = $items->firstWhere('key', 'control');
                    if ($monitorItem && (float) ($monitorItem['qty'] ?? 0) > 0) {
                        $rows[] = [
                            'جهاز المونيتر',
                            '<strong>'.e(number_format((float) ($monitorItem['total_price'] ?? 0), 0)).' ج.م</strong>',
                            true,
                        ];
                    }
                    $electricityItem = $items->firstWhere('key', 'electricity');
                    if ($electricityItem && (float) ($electricityItem['qty'] ?? 0) > 0) {
                        $rows[] = [
                            'الكهرباء ولوحات التحكم والإنارة',
                            '<strong>'.e(number_format((float) ($electricityItem['total_price'] ?? 0), 0)).' ج.م</strong>',
                            true,
                        ];
                    }

                    if ($rows === []) {
                        return new HtmlString('<p style="color:#64748b;font-size:13px;">لا توجد مشتملات اختيارية مفعّلة حالياً.</p>');
                    }

                    $html = '<table style="width:100%;font-size:13px;border-collapse:collapse;background:#f8fafc;border-radius:8px;">';
                    $html .= '<thead><tr style="background:#e2e8f0;"><th style="padding:8px;text-align:right;">البند</th><th style="padding:8px;text-align:left;">المبلغ</th></tr></thead><tbody>';
                    foreach ($rows as [$label, $val, $isHtml]) {
                        $cell = $isHtml ? $val : e((string) $val);
                        $html .= '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:8px;color:#475569;">'.e($label).'</td><td style="padding:8px;font-weight:700;text-align:left;direction:ltr;">'.$cell.'</td></tr>';
                    }
                    $html .= '</tbody></table>';

                    return new HtmlString($html);
                })
                ->visible(fn (Get $get) => static::showsAccessoriesPreview($get)),
        ];
    }

    public static function livePricingPreviewSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('live_preview_html')
                ->label('ملخص التسعير')
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
                    $scope = static::pricingScopeFromForm($get);

                    $rows = [];

                    if (static::showsBatteryPreview($get)) {
                        $rows[] = ['الطول الفعال', e(($c['effective_length'] ?? '-').' م'), false];
                        $rows[] = ['أعشاش / خط', e(number_format($c['nests_per_line'] ?? 0)), false];
                        $rows[] = ['إجمالي الأعشاش', e(number_format($c['total_nests'] ?? 0)), false];
                        $rows[] = ['طيور / عش', e(number_format($tech['birds_per_nest'] ?? 0)), false];
                        $rows[] = ['عدد الطيور', e(number_format($c['bird_count'] ?? 0)), false];
                    }

                    $rows[] = ['المجموع الفرعي', e($subtotal.' ج.م'), false];

                    if (! empty($preview['currency']['total_usd'])) {
                        $rows[] = ['بالدولار (تقريبي)', e(number_format($preview['currency']['total_usd'], 2).' $'), false];
                    }

                    if ($rows === []) {
                        return new HtmlString('<p style="color:#64748b;font-size:13px;">اختر نطاق التسعير لعرض الملخص…</p>');
                    }

                    $title = match ($scope) {
                        PoultryPricingScope::BatteriesOnly->value => 'ملخص البطاريات',
                        PoultryPricingScope::BatteriesAndAccessories->value => 'ملخص البطاريات والمشتملات',
                        PoultryPricingScope::AccessoriesOnly->value => 'ملخص المشتملات',
                        PoultryPricingScope::ConstructionOnly->value => 'ملخص الإنشاءات',
                        default => 'ملخص المشروع',
                    };

                    $html = '<p style="font-weight:700;margin:0 0 8px;color:#334155;">'.e($title).'</p>';
                    $html .= '<table style="width:100%;font-size:13px;border-collapse:collapse;">';
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
            'liveDebounce' => static::LIVE_DEBOUNCE_MS,
            'afterStateUpdated' => [$cb],
        ];
    }
}
