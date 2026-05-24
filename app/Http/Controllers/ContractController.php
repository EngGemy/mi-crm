<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractGenerator;

class ContractController extends Controller
{
    public function __construct(
        protected ContractGenerator $generator
    ) {}

    /**
     * معاينة العقد بصيغة PDF في المتصفح
     */
    public function preview(Contract $contract)
    {
        return $this->generator->streamPdf($contract);
    }

    /**
     * تحميل العقد PDF
     */
    public function download(Contract $contract)
    {
        return $this->generator->downloadPdf($contract);
    }

    /**
     * عرض HTML خام للعقد (للتجربة والتعديل)
     */
    public function html(Contract $contract)
    {
        return $this->generator->renderHtml($contract);
    }
}
