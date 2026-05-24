<?php

namespace App\Services;

use App\Models\Quotation;
use App\Support\PoultrySectionLabels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

/**
 * توليد عروض الأسعار بصيغة PDF باستخدام mPDF
 *
 * يعتمد على:
 * - carlos-meneses/laravel-mpdf
 * - Cairo font مع useOTL لدعم Arabic shaping
 * - Blade partials للتصميم
 */
class QuotationGenerator
{
    protected array $mpdfConfig;

    public function __construct()
    {
        $this->mpdfConfig = [
            'default_font' => 'cairo',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            /* بدون pad لا يُحسب ارتفاع HTML header/footer في tMargin/bMargin — يتداخل النص مع الخط الأحمر */
            'setAutoTopMargin' => 'pad',
            'setAutoBottomMargin' => 'pad',
            'custom_font_dir' => public_path('fonts/'),
            'custom_font_data' => [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ];
    }

    public function generatePdf(Quotation $quotation): string
    {
        $quotation = $this->loadRelations($quotation);
        $html = $this->renderHtml($quotation);

        $pdf = PDF::loadHTML($html, $this->mpdfConfig);

        $filename = "quotations/{$quotation->quotation_number}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    public function downloadPdf(Quotation $quotation)
    {
        $quotation = $this->loadRelations($quotation);
        $html = $this->renderHtml($quotation);

        return PDF::loadHTML($html, $this->mpdfConfig)
            ->download("Quotation_{$quotation->quotation_number}.pdf");
    }

    public function streamPdf(Quotation $quotation)
    {
        $quotation = $this->loadRelations($quotation);
        $html = $this->renderHtml($quotation);

        return PDF::loadHTML($html, $this->mpdfConfig)
            ->stream("Quotation_{$quotation->quotation_number}.pdf");
    }

    public function loadRelations(Quotation $quotation): Quotation
    {
        return $quotation->loadMissing([
            'customer',
            'quotationType',
            'items.section',
            'sectionAttachments.section',
            'termAttachments.term',
            'technicalSpecs',
            'images.imageLibrary',
            'parentQuotation',
        ]);
    }

    public function renderHtml(Quotation $quotation): string
    {
        $renderedTerms = $this->renderTerms($quotation);
        $groupedItems = $this->groupItemsBySection($quotation);
        $groupSubtotals = [];
        foreach ($groupedItems as $groupName => $group) {
            $groupSubtotals[$groupName] = collect($group)->sum(fn ($item) => (float) $item->total_price);
        }

        return View::make('quotations.template', [
            'quotation' => $quotation,
            'customer' => $quotation->customer,
            'type' => $quotation->quotationType,
            'items' => $quotation->items,
            'groupedItems' => $groupedItems,
            'groupSubtotals' => $groupSubtotals,
            'sectionAttachments' => $quotation->sectionAttachments->where('is_visible', true)->sortBy('sort_order'),
            'renderedTerms' => $renderedTerms,
            'technicalSpecs' => $quotation->technicalSpecs->sortBy('sort_order'),
            'images' => $quotation->images->sortBy('sort_order'),
            'coverImage' => $quotation->images->where('position', 'cover')->first(),
        ])->render();
    }

    public function renderTerms(Quotation $quotation): array
    {
        $result = [];
        foreach ($quotation->termAttachments->where('is_visible', true)->sortBy('sort_order') as $att) {
            $term = $att->term;
            if (! $term) {
                continue;
            }

            $content = $att->content_override ?: $term->content_ar;
            $vars = $att->variables_values ?? [];

            foreach ($vars as $key => $value) {
                $content = str_replace("{{{$key}}}", (string) $value, $content);
                $content = str_replace("{{#{$key}}}".(string) $value."{{/{$key}}}", (string) $value, $content);
                $content = str_replace("{{#{$key}}}", '', $content);
                $content = str_replace("{{/{$key}}}", '', $content);
            }

            // Clean up any remaining variables
            $content = preg_replace('/\{\{[#\/]?[a-zA-Z_][a-zA-Z0-9_]*\}\}/', '', $content);

            $result[] = [
                'attachment' => $att,
                'term' => $term,
                'rendered_content' => nl2br(e($content)),
            ];
        }

        return $result;
    }

    public function groupItemsBySection(Quotation|PoultryQuotation $quotation): array
    {
        $groups = [];

        // دعم PoultryQuotation عبر pricing_snapshot (البنود مخزنة في snapshot لا في DB)
        if ($quotation instanceof PoultryQuotation) {
            $snapshot = $quotation->pricing_snapshot ?? [];
            $snapshotItems = $snapshot['items'] ?? [];
            if (! empty($snapshotItems)) {
                foreach ($snapshotItems as $item) {
                    $groupName = PoultrySectionLabels::groupLabel($item['section'] ?? 'general');
                    $groups[$groupName][] = (object) [
                        'description_ar' => $item['desc_ar'] ?? '',
                        'description_en' => $item['desc_en'] ?? '',
                        'unit' => $item['unit'] ?? '',
                        'quantity' => $item['qty'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => $item['total_price'] ?? 0,
                        'is_taxable' => $item['is_taxable'] ?? true,
                        'section' => (object) ['category' => $item['section'] ?? ''],
                    ];
                }

                $ordered = [];
                foreach (['الإنشاءات', 'بطاريات العنبر', 'المشتملات'] as $label) {
                    if (isset($groups[$label])) {
                        $ordered[$label] = $groups[$label];
                        unset($groups[$label]);
                    }
                }
                foreach ($groups as $label => $items) {
                    $ordered[$label] = $items;
                }

                return $ordered;
            }
        }

        $isPoultry = $quotation->hall_length !== null;

        foreach ($quotation->items->sortBy('sort_order') as $item) {
            if ($isPoultry) {
                $category = $item->section?->category;
                if ($category && array_key_exists($category, PoultrySectionLabels::displayGroupsAr())) {
                    $groupName = PoultrySectionLabels::groupLabel($category);
                } else {
                    $groupName = $item->section?->title_ar ?? 'عام';
                }
            } else {
                $groupName = $item->section?->title_ar ?? 'عام';
            }
            $groups[$groupName][] = $item;
        }

        // Ensure consistent order for poultry display groups
        if ($isPoultry) {
            $ordered = [];
            foreach (['الإنشاءات', 'بطاريات العنبر', 'المشتملات'] as $label) {
                if (isset($groups[$label])) {
                    $ordered[$label] = $groups[$label];
                    unset($groups[$label]);
                }
            }
            foreach ($groups as $label => $items) {
                $ordered[$label] = $items;
            }
            $groups = $ordered;
        }

        return $groups;
    }
}
