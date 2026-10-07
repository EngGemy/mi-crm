<?php

namespace App\Quotations;

use App\Enums\PoultryProjectType;
use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Quotations\Contracts\QuotationTemplate;
use App\Quotations\Exceptions\LayerRearingDisabledException;
use App\Quotations\Templates\BroilerQuotationTemplate;
use App\Quotations\Templates\LayersQuotationTemplate;

class QuotationTemplateResolver
{
    public function __construct(
        private BroilerQuotationTemplate $broiler,
        private LayersQuotationTemplate $layers,
    ) {}

    public function resolve(PoultryQuotation $quotation): QuotationTemplate
    {
        $frozen = $this->byQuoteTypeCode($this->frozenQuoteTypeCode($quotation));
        if ($frozen !== null) {
            return $frozen;
        }

        $projectType = (string) ($quotation->project_type ?? '');
        if ($projectType === PoultryProjectType::LayerRearing->value && ! config('quotations.layer_rearing_enabled')) {
            throw new LayerRearingDisabledException;
        }

        $key = $projectType !== ''
            ? (string) config('quotations.map.'.$projectType, 'broiler')
            : 'broiler';

        return $this->byKey($key);
    }

    public function quoteTypeIdFor(PoultryQuotation $quotation): ?int
    {
        $id = Lookup::query()
            ->where('type', Lookup::TYPE_QUOTE_TYPE)
            ->where('code', $this->resolve($quotation)->quoteTypeCode())
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /** @return list<QuotationTemplate> */
    private function templates(): array
    {
        return [$this->broiler, $this->layers];
    }

    private function byKey(string $key): QuotationTemplate
    {
        foreach ($this->templates() as $template) {
            if ($template->key() === $key) {
                return $template;
            }
        }

        return $this->broiler;
    }

    private function byQuoteTypeCode(?string $code): ?QuotationTemplate
    {
        if ($code === null) {
            return null;
        }

        foreach ($this->templates() as $template) {
            if ($template->quoteTypeCode() === $code) {
                return $template;
            }
        }

        return null;
    }

    private function frozenQuoteTypeCode(PoultryQuotation $quotation): ?string
    {
        if (empty($quotation->quote_type_id)) {
            return null;
        }

        $lookup = $quotation->relationLoaded('quoteType')
            ? $quotation->getRelation('quoteType')
            : $quotation->quoteType;

        $code = $lookup?->code;

        return is_string($code) && $code !== '' ? $code : null;
    }
}
