<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractClauseAttachment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * مسؤول عن استبدال المتغيرات في نصوص البنود
 * يدعم {{var}} للمتغيرات و [[items]] للجداول
 */
class ClauseRenderer
{
    /**
     * استبدال متغيرات بند واحد
     */
    public function renderClause(ContractClauseAttachment $attachment): string
    {
        $content = $attachment->content_override ?? $attachment->clause->content;

        $variables = $this->buildVariableContext($attachment);

        // استبدال {{VAR_NAME}}
        foreach ($variables as $key => $value) {
            $content = str_ireplace('{{'.$key.'}}', (string) $value, $content);
        }

        // fallback: replace any unresolved {{VAR}} with dotted line
        if (preg_match('/\{\{[A-Z0-9_]+\}\}/', $content)) {
            preg_match_all('/\{\{([A-Z0-9_]+)\}\}/', $content, $m);
            Log::warning('ClauseRenderer: unresolved variables', [
                'clause_id' => $attachment->clause_id,
                'contract_id' => $attachment->contract_id,
                'vars' => $m[1],
            ]);
            $content = preg_replace('/\{\{[A-Z0-9_]+\}\}/', '............', $content);
        }

        // معالجة الجداول [[ITEMS_TABLE]]
        if (str_contains($content, '[[ITEMS_TABLE]]') && ! empty($attachment->items)) {
            $tableHtml = $this->renderItemsTable(
                $attachment->items,
                $attachment->clause->items_schema ?? []
            );
            $content = str_replace('[[ITEMS_TABLE]]', $tableHtml, $content);
        }

        return $content;
    }

    /**
     * بناء سياق المتغيرات (متغيرات البند + متغيرات العقد العامة)
     */
    protected function buildVariableContext(ContractClauseAttachment $attachment): array
    {
        $contract = $attachment->contract;

        // متغيرات العقد العامة
        $context = $this->getGlobalContractVariables($contract);

        // متغيرات خاصة بالبند (تطغى على العامة)
        $clauseVars = $attachment->variables_values ?? [];
        $context = array_merge($context, $clauseVars);

        return $context;
    }

    /**
     * المتغيرات العامة لكل العقود
     */
    public function getGlobalContractVariables(Contract $contract): array
    {
        $contract->loadMissing('customer', 'contractType');

        return [
            'CONTRACT_NUMBER' => $contract->contract_number ?? '',
            'PROJECT_CODE' => $contract->project_code ?? '',
            'CONTRACT_DATE' => $contract->contract_date?->format('Y/m/d') ?? '',
            'CONTRACT_DATE_AR' => $this->arabicDate($contract->contract_date),
            'CONTRACT_DAY_NAME' => $this->arabicWeekday($contract->contract_date),

            'CUSTOMER_NAME' => $contract->customer->name ?? '',
            'CUSTOMER_NAME_EN' => $contract->customer->name_en ?? '',
            'CUSTOMER_ID' => $contract->customer->national_id ?? '',
            'CUSTOMER_NATIONALITY' => $contract->customer->nationality ?? '',
            'CUSTOMER_PHONE' => $contract->customer->phone ?? '',
            'CUSTOMER_ADDRESS' => $contract->customer->address ?? '',

            'PROJECT_NAME' => $contract->project_name ?? '',
            'PROJECT_DESCRIPTION' => $contract->project_description ?? '',
            'INSTALLATION_LOCATION' => $contract->installation_location ?? '',

            'HALL_LENGTH' => $contract->hall_length ?? '',
            'HALL_WIDTH' => $contract->hall_width ?? '',
            'HALL_HEIGHT' => $contract->hall_height ?? '',
            'HALL_DIMENSIONS' => $this->formatDimensions($contract),
            'HALL_COUNT' => $contract->hall_count ?? 1,
            'CAGE_COUNT' => number_format((int) $contract->cage_count),
            'BIRD_CAPACITY' => number_format((int) $contract->bird_capacity),

            'CAGES_COST' => $this->formatMoney($contract->cages_cost),
            'CONSTRUCTION_COST' => $this->formatMoney($contract->construction_cost),
            'ELECTRICITY_COST' => $this->formatMoney($contract->electricity_cost),
            'PLUMBING_COST' => $this->formatMoney($contract->plumbing_cost),
            'ACCESSORIES_COST' => $this->formatMoney($contract->accessories_cost),
            'SUBTOTAL' => $this->formatMoney($contract->subtotal),
            'DISCOUNT_AMOUNT' => $this->formatMoney($contract->discount_amount),
            'VAT_AMOUNT' => $this->formatMoney($contract->vat_amount),
            'TOTAL_VALUE' => $this->formatMoney($contract->total_value),
            'TOTAL_VALUE_WORDS' => $this->numberToArabicWords((float) $contract->total_value),
            'CURRENCY' => $contract->currency ?? 'EGP',
            'CURRENCY_LABEL' => $this->currencyLabel($contract->currency),

            'MANUFACTURING_DAYS' => $contract->manufacturing_days ?? 105,
            'EXPECTED_DELIVERY_DATE' => $contract->expected_delivery_date?->format('Y/m/d') ?? '',
            'WARRANTY_MONTHS' => $contract->warranty_months ?? 12,
            'WARRANTY_YEARS' => $contract->manufacturing_warranty_years ?? 12,

            'PAYMENT_1_AMOUNT' => $this->formatMoney((float) $contract->total_value * $this->paymentFactor(0)),
            'PAYMENT_2_AMOUNT' => $this->formatMoney((float) $contract->total_value * $this->paymentFactor(1)),
            'PAYMENT_3_AMOUNT' => $this->formatMoney((float) $contract->total_value * $this->paymentFactor(2)),

            // جداول زمنية إضافية
            'DAYS_TESTING' => 30,
            'DAYS_TRAINING' => 7,
            'COUNT_WORKERS' => 2,
            'DAYS_NOTIFY' => 7,

            // ضمانات إضافية
            'YEARS_WARRANTY_STEEL' => $contract->manufacturing_warranty_years ?? 12,
            'MONTHS_WARRANTY_MANUFACTURING' => $contract->warranty_months ?? 12,
            'YEARS_SPARE_PARTS' => 3,

            // غرامات
            'AMOUNT_PENALTY' => $this->formatMoney((float) $contract->total_value * 0.005),
            'DAYS_PENALTY_MAX' => 30,

            // سرية وقانون
            'YEARS_CONFIDENTIALITY' => 5,
            'DAYS_NEGOTIATION' => 15,
            'LOCATION_COURT' => $contract->installation_location ?? '',
            'JURISDICTION_LEGAL' => $contract->installation_location ?? '',
        ];
    }

    /**
     * نسبة دفعة من جدول الإعدادات (fallback: 70/25/5)
     */
    protected function paymentFactor(int $index): float
    {
        static $defaults = [70, 25, 5];

        $raw = settings('finance.payment_schedule');
        if ($raw) {
            $schedule = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($schedule) && isset($schedule[$index]['percentage'])) {
                return (float) $schedule[$index]['percentage'] / 100;
            }
        }

        return ($defaults[$index] ?? 0) / 100;
    }

    /**
     * تحويل رقم لكتابة عربية
     */
    public function numberToArabicWords(float $number): string
    {
        $n = (int) round($number);
        if ($n === 0) {
            return 'صفر جنيه';
        }

        $units = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $tens = ['', 'عشرة', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

        $belowThousand = function (int $num) use ($units, $tens, $teens, $hundreds) {
            if ($num === 0) {
                return '';
            }
            $parts = [];
            $h = intdiv($num, 100);
            $rem = $num % 100;
            if ($h > 0) {
                $parts[] = $hundreds[$h];
            }
            if ($rem >= 10 && $rem < 20) {
                $parts[] = $teens[$rem - 10];
            } elseif ($rem > 0 && $rem < 10) {
                $parts[] = $units[$rem];
            } elseif ($rem >= 20) {
                $u = $rem % 10;
                $t = intdiv($rem, 10);
                $parts[] = $u > 0 ? $units[$u].' و'.$tens[$t] : $tens[$t];
            }

            return implode(' و', $parts);
        };

        if ($n < 1000) {
            return $belowThousand($n).' جنيه مصري لا غير';
        }

        $parts = [];
        $millions = intdiv($n, 1_000_000);
        $thousands = intdiv($n % 1_000_000, 1000);
        $remainder = $n % 1000;

        if ($millions > 0) {
            $parts[] = match (true) {
                $millions === 1 => 'مليون',
                $millions === 2 => 'مليونان',
                $millions <= 10 => $belowThousand($millions).' ملايين',
                default => $belowThousand($millions).' مليون',
            };
        }
        if ($thousands > 0) {
            $parts[] = match (true) {
                $thousands === 1 => 'ألف',
                $thousands === 2 => 'ألفان',
                $thousands <= 10 => $belowThousand($thousands).' آلاف',
                default => $belowThousand($thousands).' ألف',
            };
        }
        if ($remainder > 0) {
            $parts[] = $belowThousand($remainder);
        }

        return implode(' و', $parts).' جنيه مصري لا غير';
    }

    public function arabicWeekday($date): string
    {
        if (! $date) {
            return '';
        }
        $days = ['الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'];

        return $days[Carbon::parse($date)->dayOfWeekIso - 1] ?? '';
    }

    public function arabicDate($date): string
    {
        if (! $date) {
            return '';
        }

        return Carbon::parse($date)->locale('ar')->translatedFormat('j F Y');
    }

    protected function formatMoney($amount): string
    {
        return number_format((float) $amount, 0);
    }

    protected function currencyLabel(?string $code): string
    {
        return match ($code) {
            'EGP' => 'جنيه مصري',
            'SAR' => 'ريال سعودي',
            'USD' => 'دولار أمريكي',
            'AED' => 'درهم إماراتي',
            default => $code ?? 'جنيه مصري',
        };
    }

    protected function formatDimensions(Contract $contract): string
    {
        if (! $contract->hall_length || ! $contract->hall_width) {
            return '';
        }
        $h = $contract->hall_height ? "×{$contract->hall_height}" : '';

        return "{$contract->hall_length}×{$contract->hall_width}{$h} متر";
    }

    /**
     * توليد جدول HTML من بيانات items
     */
    protected function renderItemsTable(array $items, array $schema): string
    {
        if (empty($items) || empty($schema)) {
            return '';
        }

        $html = '<table class="items-table" style="width:100%;border-collapse:collapse;margin:10px 0;">';

        // Header
        $html .= '<thead><tr>';
        foreach ($schema as $col) {
            $label = $col['label'] ?? $col['key'];
            $html .= "<th style=\"background:#C00000;color:white;padding:8px;border:1px solid #808080;\">{$label}</th>";
        }
        $html .= '</tr></thead>';

        // Body
        $html .= '<tbody>';
        $totals = [];
        foreach ($items as $item) {
            $html .= '<tr>';
            foreach ($schema as $col) {
                $value = $item[$col['key']] ?? '';
                if (($col['type'] ?? null) === 'money') {
                    $value = number_format((float) $value, 0);
                    $totals[$col['key']] = ($totals[$col['key']] ?? 0) + (float) ($item[$col['key']] ?? 0);
                }
                $html .= "<td style=\"padding:8px;border:1px solid #808080;\">{$value}</td>";
            }
            $html .= '</tr>';
        }

        // Totals row
        if (! empty($totals)) {
            $html .= '<tr style="background:#F2F2F2;font-weight:bold;">';
            $first = true;
            foreach ($schema as $col) {
                if ($first) {
                    $html .= '<td style="padding:8px;border:1px solid #808080;font-weight:bold;">الإجمالي</td>';
                    $first = false;

                    continue;
                }
                $value = isset($totals[$col['key']]) ? number_format($totals[$col['key']], 0) : '';
                $html .= "<td style=\"padding:8px;border:1px solid #808080;font-weight:bold;\">{$value}</td>";
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
