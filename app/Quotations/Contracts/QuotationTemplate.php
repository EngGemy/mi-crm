<?php

namespace App\Quotations\Contracts;

use App\Models\PoultryQuotation;
use Illuminate\Http\Response;

interface QuotationTemplate
{
    public function key(): string;

    public function label(): string;

    public function quoteTypeCode(): string;

    public function supports(string $projectType): bool;

    /** @return array<string, mixed> */
    public function buildData(PoultryQuotation $q): array;

    public function render(PoultryQuotation $q): Response;

    /** @return array<string, mixed> */
    public function shareCardData(PoultryQuotation $q): array;
}
