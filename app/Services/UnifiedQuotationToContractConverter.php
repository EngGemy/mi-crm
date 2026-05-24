<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractType;
use App\Models\PoultryQuotation;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

/**
 * مُحوّل موحّد: عرض سعر → عقد.
 *
 * - يُلغي الفرق بين QuotationToContractConverter و PoultryQuotationToContractConverter.
 * - ينسخ snapshot المالي حرفيًا (subtotal/discount/vat/total) بدون إعادة اشتقاق.
 * - يوزّع البنود على حقول العقد حسب الأقسام.
 * - البوابة الوحيدة: status = 'approved'.
 */
class UnifiedQuotationToContractConverter
{
    /**
     * تحويل Quotation عام إلى عقد.
     *
     * @throws \Exception
     */
    public function convertQuotation(Quotation $quotation, array $additionalData = []): Contract
    {
        $this->assertCanConvert($quotation);

        return DB::transaction(function () use ($quotation, $additionalData) {
            $snapshot = $quotation->pricing_snapshot ?? [];
            $financial = $snapshot['financial'] ?? [];

            $contract = Contract::create([
                'customer_id' => $quotation->customer_id,
                'contract_type_id' => $additionalData['contract_type_id']
                    ?? $this->guessContractType($quotation),
                'project_name' => $quotation->project_name,
                'project_description' => $quotation->project_description,
                'installation_location' => $quotation->installation_location,
                'hall_length' => $quotation->hall_length,
                'hall_width' => $quotation->hall_width,
                'hall_height' => $quotation->hall_height,
                'hall_count' => $quotation->hall_count ?? 1,
                'cage_count' => $quotation->cage_count,
                'bird_capacity' => $quotation->bird_capacity,
                'currency' => $quotation->currency,
                'exchange_rate' => $quotation->exchange_rate,
                'discount_percentage' => $quotation->discount_percentage,
                'discount_amount' => $financial['discount_amount'] ?? $quotation->discount_amount,
                'vat_percentage' => $quotation->vat_percentage,
                'vat_amount' => $financial['vat_amount'] ?? $quotation->vat_amount,
                'total_value' => $financial['total'] ?? $quotation->total_amount,
                'cages_cost' => $quotation->subtotal,
                'quotation_id' => $quotation->id,
                'contract_date' => $additionalData['contract_date'] ?? now(),
                'expected_delivery_date' => $additionalData['expected_delivery_date']
                    ?? now()->addDays((int) settings('defaults.manufacturing_days', 105)),
                'manufacturing_days' => $additionalData['manufacturing_days']
                    ?? settings('defaults.manufacturing_days', 105),
                'status' => 'draft',
                'created_by' => auth()->id(),
                'internal_notes' => $quotation->notes,
            ]);

            // نسخ البنود
            foreach ($quotation->items as $quotationItem) {
                $contract->items()->create([
                    'product_id' => null,
                    'description' => $quotationItem->description_ar,
                    'quantity' => $quotationItem->quantity,
                    'unit' => $quotationItem->unit,
                    'unit_price' => $quotationItem->unit_price,
                    'discount_percentage' => $quotationItem->discount_percentage ?? 0,
                    'total_price' => $quotationItem->total_price,
                    'is_taxable' => $quotationItem->is_taxable ?? true,
                    'sort_order' => $quotationItem->sort_order,
                    'section' => 'cages',
                ]);
            }

            // نسخ الشروط إلى بنود
            foreach ($quotation->termAttachments as $termAttachment) {
                $clause = $this->mapTermToClause($termAttachment->term);
                if ($clause) {
                    $contract->clauseAttachments()->create([
                        'contract_clause_id' => $clause->id,
                        'content_override' => $termAttachment->content_override,
                        'variables_values' => $termAttachment->variables_values,
                        'sort_order' => $termAttachment->sort_order,
                        'is_visible' => true,
                    ]);
                }
            }

            // توليد جدول الدفعات
            app(PaymentScheduler::class)->generateForContract($contract);

            // تحديث حالة العرض
            $quotation->update([
                'status' => 'converted',
                'converted_at' => now(),
                'contract_id' => $contract->id,
            ]);

            return $contract;
        });
    }

    /**
     * تحويل PoultryQuotation إلى عقد.
     *
     * @param  PoultryQuotation  $quotation
     *
     * @throws \InvalidArgumentException
     */
    public function convertPoultryQuotation($quotation, array $additionalData = []): Contract
    {
        $this->assertCanConvert($quotation);

        $snapshot = $quotation->pricing_snapshot ?? [];
        $financial = $snapshot['financial'] ?? [];

        return DB::transaction(function () use ($quotation, $additionalData, $snapshot, $financial) {
            $contract = Contract::create([
                'customer_id' => $additionalData['customer_id'] ?? null,
                'contract_type_id' => $additionalData['contract_type_id'] ?? null,
                'project_name' => $additionalData['project_name'] ?? "عنبر {$quotation->client_name}",
                'project_description' => $additionalData['project_description'] ?? ($snapshot['project_type'] ?? 'broiler'),
                'installation_location' => $quotation->client_address,
                'hall_length' => $quotation->length,
                'hall_width' => $quotation->width,
                'hall_height' => $quotation->height,
                'hall_count' => 1,
                'bird_capacity' => $quotation->bird_count,
                'currency' => $additionalData['currency'] ?? 'EGP',
                'discount_percentage' => 0,
                'discount_amount' => $financial['discount_amount'] ?? '0.00',
                'vat_percentage' => $quotation->vat_percentage,
                'vat_amount' => $financial['vat_amount'] ?? $quotation->vat_amount,
                'total_value' => $financial['total'] ?? $quotation->total,
                'cages_cost' => $financial['subtotal'] ?? $quotation->subtotal,
                'quotation_id' => $quotation->quotation_id ?? null,
                'contract_date' => $additionalData['contract_date'] ?? now(),
                'expected_delivery_date' => $additionalData['expected_delivery_date']
                    ?? now()->addDays((int) settings('defaults.manufacturing_days', 105)),
                'manufacturing_days' => $additionalData['manufacturing_days']
                    ?? (int) settings('defaults.manufacturing_days', 105),
                'status' => 'draft',
                'created_by' => auth()->id(),
                'internal_notes' => "من عرض السعر: {$quotation->quote_number}",
            ]);

            foreach ($snapshot['items'] ?? [] as $idx => $row) {
                $contract->items()->create([
                    'description' => $row['desc_ar'] ?? $row['desc_en'],
                    'quantity' => $row['qty'],
                    'unit' => $row['unit'],
                    'unit_price' => $row['unit_price'],
                    'total_price' => $row['total_price'],
                    'discount_percentage' => 0,
                    'is_taxable' => $row['is_taxable'] ?? true,
                    'sort_order' => $idx,
                    'section' => $row['section'] ?? 'general',
                ]);
            }

            $quotation->update(['contract_id' => $contract->id]);

            return $contract->fresh('items');
        });
    }

    /**
     * التحقق من صلاحية التحويل.
     *
     * @param  Quotation|PoultryQuotation  $quotation
     *
     * @throws \Exception|\InvalidArgumentException
     */
    protected function assertCanConvert($quotation): void
    {
        $status = $quotation->status;

        if ($status !== 'approved') {
            throw new \Exception('لا يمكن تحويل عرض غير موافق عليه');
        }

        if ($quotation->contract_id) {
            throw new \Exception('هذا العرض تم تحويله مسبقاً');
        }
    }

    protected function guessContractType(Quotation $quotation): int
    {
        $typeCode = $quotation->quotationType->code ?? null;

        $mapping = [
            'CONSTRUCTION_ONLY' => 'CONSTRUCTION_ONLY',
            'CAGES_ONLY' => 'CAGES_ONLY',
            'FULL_PROJECT' => 'FATTENING_FULL',
            'ACCESSORIES_ONLY' => 'ACCESSORIES_ONLY',
        ];

        $contractTypeCode = $mapping[$typeCode] ?? 'FATTENING_FULL';

        return ContractType::where('code', $contractTypeCode)
            ->first()?->id ?? 1;
    }

    protected function mapTermToClause($term): ?ContractClause
    {
        if (! $term) {
            return null;
        }

        return ContractClause::where('category', $term->code)->first()
            ?? ContractClause::where('title', 'LIKE', "%{$term->title_ar}%")->first();
    }
}
