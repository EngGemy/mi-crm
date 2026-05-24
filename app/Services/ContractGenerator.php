<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

/**
 * توليد العقود بصيغة PDF باستخدام mPDF
 *
 * يعتمد على:
 * - carlos-meneses/laravel-mpdf (mPDF wrapper)
 * - Cairo font مع useOTL لدعم Arabic shaping الكامل
 * - Blade template للتصميم
 */
class ContractGenerator
{
    /** @var array<string, mixed> */
    protected array $mpdfConfig;

    public function __construct(
        protected ClauseRenderer $renderer
    ) {
        $this->mpdfConfig = [
            'default_font' => 'cairo',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'setAutoTopMargin' => 'pad',
            'setAutoBottomMargin' => 'pad',
            'custom_font_dir' => storage_path('fonts/'),
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

    /**
     * توليد PDF للعقد وحفظه في Storage
     */
    public function generatePdf(Contract $contract): string
    {
        $contract->loadMissing([
            'customer', 'contractType',
            'items.product',
            'clauseAttachments.clause',
            'milestones',
            'payments',
        ]);

        $html = $this->renderHtml($contract);

        $pdf = PDF::loadHTML($html, $this->mpdfConfig);

        $filename = "contracts/{$contract->contract_number}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * تحميل PDF مباشرة (للـ Filament)
     */
    public function downloadPdf(Contract $contract)
    {
        $contract->loadMissing([
            'customer', 'contractType',
            'items.product',
            'clauseAttachments.clause',
            'milestones', 'payments',
        ]);

        $html = $this->renderHtml($contract);

        return PDF::loadHTML($html, $this->mpdfConfig)
            ->download("Contract_{$contract->contract_number}.pdf");
    }

    /**
     * عرض PDF في المتصفح
     */
    public function streamPdf(Contract $contract)
    {
        $contract->loadMissing([
            'customer', 'contractType',
            'items.product',
            'clauseAttachments.clause',
            'milestones', 'payments',
        ]);

        $html = $this->renderHtml($contract);

        return PDF::loadHTML($html, $this->mpdfConfig)
            ->stream("Contract_{$contract->contract_number}.pdf");
    }

    /**
     * بناء الـ HTML للعقد من Blade template
     */
    public function renderHtml(Contract $contract): string
    {
        // معالجة محتوى كل بند مع المتغيرات
        $renderedClauses = $contract->clauseAttachments
            ->where('is_visible', true)
            ->sortBy('sort_order')
            ->map(function ($attachment) {
                return [
                    'attachment' => $attachment,
                    'clause' => $attachment->clause,
                    'rendered_content' => $this->renderer->renderClause($attachment),
                ];
            });

        return View::make('contracts.template', [
            'contract' => $contract,
            'renderedClauses' => $renderedClauses,
            'globalVars' => $this->renderer->getGlobalContractVariables($contract),
        ])->render();
    }

    /**
     * توليد ملف Word (.docx) من العقد
     * يستخدم phpoffice/phpword
     */
    public function generateDocx(Contract $contract): string
    {
        // متروك للـ team لتطبيقه باستخدام phpoffice/phpword
        // حالياً نستخدم PDF كخيار رئيسي
        throw new \Exception('Word generation not yet implemented. Use PDF for now.');
    }
}
