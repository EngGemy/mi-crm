<?php

namespace App\Quotations\Templates;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Quotations\Contracts\QuotationTemplate;
use App\Services\Poultry\MiProposalPdfGenerator;
use App\Services\Poultry\ProposalPage2Data;
use App\Services\Poultry\ProposalPage6Data;
use App\Services\Poultry\ProposalPage9Data;
use App\Services\Poultry\ProposalPage10Data;
use Illuminate\Http\Response;

class BroilerQuotationTemplate implements QuotationTemplate
{
    public function key(): string
    {
        return 'broiler';
    }

    public function label(): string
    {
        return 'بطاريات تسمين';
    }

    public function quoteTypeCode(): string
    {
        return 'batteries_broiler_eg';
    }

    public function supports(string $projectType): bool
    {
        if ($this->rearingDisabled($projectType)) {
            return false;
        }

        return (string) config('quotations.map.'.$projectType, 'broiler') === $this->key();
    }

    public function buildData(PoultryQuotation $q): array
    {
        return [
            'page2' => (new ProposalPage2Data)->from($q),
            'page6' => (new ProposalPage6Data)->from($q),
            'page9' => (new ProposalPage9Data)->from($q),
            'page10' => (new ProposalPage10Data)->from($q),
        ];
    }

    public function render(PoultryQuotation $q): Response
    {
        return app(MiProposalPdfGenerator::class)->download($q);
    }

    public function shareCardData(PoultryQuotation $q): array
    {
        return [
            'template' => $this->key(),
            'quote_number' => $q->quote_number,
            'client_name' => $q->client_name,
            'project_type' => $q->project_type,
            'total' => $q->total,
            'currency' => 'EGP',
        ];
    }

    private function rearingDisabled(string $projectType): bool
    {
        return $projectType === PoultryProjectType::LayerRearing->value
            && ! config('quotations.layer_rearing_enabled');
    }
}
