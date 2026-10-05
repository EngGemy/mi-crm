<?php

namespace Tests\Feature;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ContractVatChoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_turning_tax_off_clears_the_percentage(): void
    {
        $data = ContractResource::applyVatChoice([
            'cages_cost' => 1000,
            'vat_percentage' => 14,
        ], false);

        $this->assertSame(0, $data['vat_percentage']);
    }

    public function test_contract_hides_the_tax_row_unless_tax_is_included(): void
    {
        $withoutTax = $this->renderFinancials(vatPercentage: 0, vatAmount: 0, total: 1000);
        $withTax = $this->renderFinancials(vatPercentage: 14, vatAmount: 140, total: 1140);

        $this->assertStringNotContainsString('ضريبة القيمة المضافة', $withoutTax);
        $this->assertStringContainsString('المجموع', $withoutTax);
        $this->assertStringNotContainsString('قبل الضريبة', $withoutTax);

        $this->assertStringContainsString('ضريبة القيمة المضافة (14%)', $withTax);
        $this->assertStringContainsString('المجموع قبل الضريبة', $withTax);
    }

    private function renderFinancials(float $vatPercentage, float $vatAmount, float $total): string
    {
        $contract = new Contract([
            'cages_cost' => 1000,
            'subtotal' => 1000,
            'discount_amount' => 0,
            'vat_percentage' => $vatPercentage,
            'vat_amount' => $vatAmount,
            'total_value' => $total,
            'currency' => 'EGP',
        ]);
        $contract->setRelation('payments', new Collection);

        return view('contracts.partials.financials', ['contract' => $contract])->render();
    }
}
