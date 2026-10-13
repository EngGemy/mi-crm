<?php

namespace App\Services\Poultry;

use App\Models\PoultryQuotation;

/**
 * أرقام الإنشاءات من أبعاد العنبر وأسعار الإعدادات.
 */
class ConstructionSummary
{
    /**
     * @param  array{concrete: float, steel: float, wall: float, tanks: float, include_tanks: bool}  $rates
     * @return array{rows: list<array{0: string, 1: string, 2: bool}>, total: float, message: string}
     */
    public function lines(float $length, float $width, float $height, array $rates): array
    {
        $concreteQty = $length * $width;
        $steelQty = $length * $width;
        $wallsQty = $length * $height * 2;
        $concrete = round($concreteQty * $rates['concrete']);
        $steel = round($steelQty * $rates['steel']);
        $walls = round($wallsQty * $rates['wall']);
        $tanks = ($rates['include_tanks'] ?? true) ? round($rates['tanks']) : 0.0;
        $total = $concrete + $steel + $walls + $tanks;

        $rows = [
            ['الخرسانات', $this->qty($concreteQty).' م² — '.VentilationOffer::money($concrete), false],
            ['الجملون والاستيل', $this->qty($steelQty).' م² — '.VentilationOffer::money($steel), false],
            ['الحوائط', $this->qty($wallsQty).' م² — '.VentilationOffer::money($walls), false],
        ];
        if ($tanks > 0) {
            $rows[] = ['الخزانات', VentilationOffer::money($tanks), false];
        }
        $rows[] = ['إجمالي الإنشاءات', VentilationOffer::money($total), false];

        $message = 'عرض الإنشاءات حسب أبعاد العنبر '.$this->qty($length).' × '.$this->qty($width).' × '.$this->qty($height).' متر:';
        foreach ($rows as [$label, $value]) {
            $message .= "\n".$label.': '.$value;
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'message' => $message,
        ];
    }

    /**
     * @return array{rows: list<array{0: string, 1: string, 2: bool}>, total: float, message: string}|null
     */
    public function forQuotation(PoultryQuotation $quotation): ?array
    {
        $length = (float) $quotation->length;
        $width = (float) $quotation->width;
        $height = (float) $quotation->height;
        if ($length <= 0 || $width <= 0 || $height <= 0) {
            return null;
        }

        $params = app(PoultryConfigLoader::class)->loadPricingParams($quotation->wall_type);

        return $this->lines($length, $width, $height, [
            'concrete' => (float) $params['concrete_cost_per_m2'],
            'steel' => (float) $params['steel_cost_per_m2'],
            'wall' => (float) $params['wall_cost_per_m2'],
            'tanks' => (float) $params['tanks_fixed_cost'],
            'include_tanks' => (bool) $params['include_tanks'],
        ]);
    }

    private function qty(float $value): string
    {
        return VentilationOffer::plain($value);
    }
}
