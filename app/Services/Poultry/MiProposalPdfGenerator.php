<?php

namespace App\Services\Poultry;

use App\Models\PoultryQuotation;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use RuntimeException;
use Throwable;

/**
 * Builds the 13-page branded "Technical Proposal" PDF.
 *
 * Static brochure pages are imported verbatim from the template; data pages
 * (2 = barn card, 6 = belts, 9 = technical specs, 10 = financial offer) are re-rendered
 * from the quotation snapshot on top of the branded header and footer.
 */
class MiProposalPdfGenerator
{
    public function download(PoultryQuotation $q): \Illuminate\Http\Response
    {
        $mpdf = $this->build($q);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="MI-Proposal-'.$q->quote_number.'.pdf"',
        ]);
    }

    public function stream(PoultryQuotation $q): \Illuminate\Http\Response
    {
        $mpdf = $this->build($q);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="MI-Proposal-'.$q->quote_number.'.pdf"',
        ]);
    }

    public function saveTo(PoultryQuotation $q, string $absolutePath): string
    {
        $this->build($q)->Output($absolutePath, 'F');

        return $absolutePath;
    }

    public function build(PoultryQuotation $q): Mpdf
    {
        try {
            $templatePath = config('mi_proposal.template_path');
            if (! is_file($templatePath)) {
                throw new RuntimeException("قالب العرض غير موجود: {$templatePath}");
            }

            $tempDir = storage_path('app/mpdf-temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_left' => 0,
                'margin_right' => 0,
                'tempDir' => $tempDir,
                'default_font' => 'cairo',
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
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

            $pageCount = (int) config('mi_proposal.page_count', 13);
            $dynamicPages = (array) config('mi_proposal.dynamic_pages', [9, 10]);
            $mpdf->setSourceFile($templatePath);

            for ($n = 1; $n <= $pageCount; $n++) {
                if ($n > 1) {
                    $mpdf->AddPage();
                }

                // Lay down the exact branded page (header, footer, watermark).
                $tplId = $mpdf->importPage($n);
                $mpdf->useTemplate($tplId);

                if (in_array($n, $dynamicPages, true)) {
                    $this->renderDynamicPage($mpdf, $n, $q);
                }
            }

            return $mpdf;
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('تعذر إنشاء ملف العرض الفني: '.$e->getMessage(), 0, $e);
        }
    }

    protected function renderDynamicPage(Mpdf $mpdf, int $page, PoultryQuotation $q): void
    {
        match ($page) {
            2 => $this->renderPage2($mpdf, $q),
            6 => $this->renderPage6($mpdf, $q),
            9 => $this->renderPage9($mpdf, $q),
            10 => $this->renderPage10($mpdf, $q),
            default => null,
        };
    }

    protected function renderPage2(Mpdf $mpdf, PoultryQuotation $q): void
    {
        $region = config('mi_proposal.content_region_page2')
            ?? config('mi_proposal.content_region');
        $this->paintContentPanel($mpdf, $region);

        $data = $this->page2Data($q);
        $mpdf->WriteHTML((new ProposalPage2Data)->stylesheet($data['primary']), HTMLParserMode::HEADER_CSS);

        $html = view('poultry.proposal.page2', $data)->render();

        $mpdf->WriteFixedPosHTML(
            $html,
            $region['x'],
            $region['y'],
            $region['w'],
            $region['h'],
            'auto'
        );
    }

    protected function renderPage6(Mpdf $mpdf, PoultryQuotation $q): void
    {
        $data = (new ProposalPage6Data)->from($q);
        $mpdf->WriteHTML((new ProposalPage6Data)->stylesheet(), HTMLParserMode::HEADER_CSS);
        $mpdf->SetFillColor(247, 237, 236);

        // Cover the blank motor line and the fixed "2 سير" line. Card fill is warm pink, not white.
        $this->paintPt($mpdf, 324, 380, 220, 40);
        $this->paintPt($mpdf, 32, 574, 236, 44);

        $mpdf->WriteFixedPosHTML(
            view('poultry.proposal.page6-motors', $data)->render(),
            $this->mm(328),
            $this->mm(382),
            $this->mm(210),
            $this->mm(36),
            'auto'
        );
        $mpdf->WriteFixedPosHTML(
            view('poultry.proposal.page6-belts', $data)->render(),
            $this->mm(36),
            $this->mm(576),
            $this->mm(226),
            $this->mm(40),
            'auto'
        );
    }

    protected function renderPage9(Mpdf $mpdf, PoultryQuotation $q): void
    {
        $region = config('mi_proposal.content_region');
        $this->paintContentPanel($mpdf, $region);

        $data = $this->page9Data($q);
        // Style tags inside WriteFixedPosHTML are printed as text. Load CSS first.
        $mpdf->WriteHTML((new ProposalPage9Data)->stylesheet($data['primary']), HTMLParserMode::HEADER_CSS);

        $html = view('poultry.proposal.page9', $data)->render();

        $mpdf->WriteFixedPosHTML(
            $html,
            $region['x'],
            $region['y'],
            $region['w'],
            $region['h'],
            'auto'
        );
    }

    protected function renderPage10(Mpdf $mpdf, PoultryQuotation $q): void
    {
        $region = config('mi_proposal.content_region_page10')
            ?? config('mi_proposal.content_region');
        $this->paintContentPanel($mpdf, $region);

        $data = $this->page10Data($q);
        $mpdf->WriteHTML((new ProposalPage10Data)->stylesheet($data['primary']), HTMLParserMode::HEADER_CSS);

        $html = view('poultry.proposal.page10', $data)->render();

        $mpdf->WriteFixedPosHTML(
            $html,
            $region['x'],
            $region['y'],
            $region['w'],
            $region['h'],
            'auto'
        );
    }

    protected function mm(float $pt): float
    {
        return $pt * 25.4 / 72;
    }

    protected function paintPt(Mpdf $mpdf, float $x, float $y, float $w, float $h): void
    {
        $mpdf->Rect($this->mm($x), $this->mm($y), $this->mm($w), $this->mm($h), 'F');
    }

    /** @param  array{x:float|int,y:float|int,w:float|int,h:float|int}  $region */
    protected function paintContentPanel(Mpdf $mpdf, array $region): void
    {
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($region['x'], $region['y'], $region['w'], $region['h'], 'F');
    }

    /** @return array<string, mixed> */
    protected function page2Data(PoultryQuotation $q): array
    {
        return (new ProposalPage2Data)->from($q);
    }

    /** @return array{primary: string, sections: list<array<string, mixed>>} */
    protected function page9Data(PoultryQuotation $q): array
    {
        return (new ProposalPage9Data)->from($q);
    }

    /** @return array<string, mixed> */
    protected function page10Data(PoultryQuotation $q): array
    {
        return (new ProposalPage10Data)->from($q);
    }

}

