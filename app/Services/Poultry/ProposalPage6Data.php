<?php

namespace App\Services\Poultry;

use App\Models\PoultryQuotation;

/**
 * Page 6 belt and manure-motor values, taken from the quotation form.
 *
 * The brochure leaves these blanks, except a fixed red "2" on the cross
 * conveyor line. That number is not the form's belt count.
 */
class ProposalPage6Data
{
    public function stylesheet(): string
    {
        return <<<'CSS'
.p6 { font-family: 'cairo', sans-serif; direction: rtl; color: #222; font-size: 11pt; line-height: 1.45; }
.p6 b { color: #ff0000; font-weight: bold; }
CSS;
    }

    /** @return array{motor_count: string, motor_power: string, belts: string, inner_belt: string, outer_belt: string} */
    public function from(PoultryQuotation $quotation): array
    {
        return [
            'motor_count' => $this->label($quotation, 'manureMotorCount', '4 ماتور'),
            'motor_power' => $this->label($quotation, 'motorPower', '1 حصان'),
            'belts' => $this->label($quotation, 'beltsPerLine', '3 سيور'),
            'inner_belt' => $this->label($quotation, 'innerBeltLength', '20 متر'),
            'outer_belt' => $this->label($quotation, 'outerBeltLength', '12 متر'),
        ];
    }

    private function label(PoultryQuotation $quotation, string $relation, string $fallback): string
    {
        $label = $quotation->{$relation}?->label_ar;

        return is_string($label) && $label !== '' ? $label : $fallback;
    }
}
