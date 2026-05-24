<?php

namespace App\Models;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Models\Concerns\NormalizesMoneyAttributes;
use App\Services\PoultryHousePricingService;
use App\Support\FinancialEngine;
use App\Support\TaxResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PoultryQuotation extends Model
{
    use HasFactory, NormalizesMoneyAttributes;

    protected $table = 'poultry_quotations';

    protected $fillable = [
        'quote_number',
        'client_name',
        'client_phone',
        'client_address',
        'project_type',
        'pricing_scope',
        'length',
        'width',
        'height',
        'wall_type',
        'tiers',
        'lines',
        'dead_zone',
        'service_length',
        'bird_weight_kg',
        'birds_per_nest',
        'side_fans_count',
        'heaters_count',
        'bird_count',
        'total_nests',
        'nests_per_line',
        'back_fans_count',
        'cooling_units',
        'windows_count',
        'concrete_cost',
        'steel_cost',
        'walls_cost',
        'tanks_cost',
        'battery_cost',
        'back_fans_cost',
        'cooling_cost',
        'windows_cost',
        'side_fans_cost',
        'heaters_cost',
        'control_cost',
        'subtotal',
        'vat_amount',
        'total',
        'vat_percentage',
        'status',
        'contract_id',
        'image_path',
        'pricing_snapshot',
        'created_by',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'dead_zone' => 'decimal:2',
        'service_length' => 'decimal:2',
        'bird_weight_kg' => 'decimal:3',
        'cooling_units' => 'decimal:2',
        'concrete_cost' => 'decimal:2',
        'steel_cost' => 'decimal:2',
        'walls_cost' => 'decimal:2',
        'tanks_cost' => 'decimal:2',
        'battery_cost' => 'decimal:2',
        'back_fans_cost' => 'decimal:2',
        'cooling_cost' => 'decimal:2',
        'side_fans_cost' => 'decimal:2',
        'heaters_cost' => 'decimal:2',
        'control_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'pricing_snapshot' => 'array',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'sent' => 'مرسل',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
        'approved' => 'معتمد',
    ];

    protected static function booted(): void
    {
        static::creating(function (PoultryQuotation $quotation) {
            if (empty($quotation->quote_number)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $quotation->quote_number = "Q-{$year}-".str_pad($count, 5, '0', STR_PAD_LEFT);
            }

            $quotation->created_by = auth()->id() ?? $quotation->created_by;
            $quotation->project_type ??= PoultryProjectType::Broiler->value;
            $quotation->pricing_scope ??= PoultryPricingScope::FullProject->value;
        });

        static::saving(function (PoultryQuotation $quotation) {
            // إذا وجد snapshot مالي محفوظ، لا نعيد الحساب — العرض المحفوظ ثابت محاسبيًا
            $snapshot = $quotation->pricing_snapshot ?? [];
            if (! empty($snapshot['financial'])) {
                $financial = $snapshot['financial'];
                $quotation->subtotal = FinancialEngine::toFloat($financial['subtotal']);
                $quotation->vat_amount = FinancialEngine::toFloat($financial['vat_amount']);
                $quotation->total = FinancialEngine::toFloat($financial['total']);

                // استعادة القيم التقنية من snapshot أيضاً
                $computed = $snapshot['computed'] ?? [];
                $quotation->bird_count = $computed['bird_count'] ?? $quotation->bird_count;
                $quotation->total_nests = $computed['total_nests'] ?? $quotation->total_nests;
                $quotation->nests_per_line = $computed['nests_per_line'] ?? $quotation->nests_per_line;
                $quotation->back_fans_count = $computed['back_fans_count'] ?? $quotation->back_fans_count;
                $quotation->cooling_units = $computed['cooling_units'] ?? $quotation->cooling_units;
                $quotation->windows_count = $computed['windows_count'] ?? $quotation->windows_count;
                $quotation->side_fans_count = $computed['side_fans_count'] ?? $quotation->side_fans_count;
                $quotation->heaters_count = $computed['heaters_count'] ?? $quotation->heaters_count;

                return;
            }

            if ($quotation->length > 0 && $quotation->width > 0 && $quotation->height > 0) {
                try {
                    $quotation->autoCompute();
                } catch (\Throwable) {
                    // silently fail during seeding or incomplete saves
                }
            }
        });
    }

    public function autoCompute(): void
    {
        $service = new PoultryHousePricingService;

        $input = [
            'project_type' => $this->project_type ?? PoultryProjectType::Broiler->value,
            'pricing_scope' => $this->pricing_scope ?? PoultryPricingScope::FullProject->value,
            'hall_length' => (float) $this->length,
            'hall_width' => (float) $this->width,
            'hall_height' => (float) $this->height,
            'service_length' => (float) ($this->service_length ?? $this->dead_zone ?? 10),
            'tiers' => (int) $this->tiers,
            'lines' => (int) $this->lines,
            'bird_weight_kg' => $this->bird_weight_kg ? (float) $this->bird_weight_kg : 2.1,
            'birds_per_nest' => $this->birds_per_nest,
            'side_fans_count' => $this->side_fans_count,
            'heaters_count' => $this->heaters_count,
            'wall_type' => $this->wall_type,
        ];

        $result = $service->compute($input);
        $computed = $result['computed'];
        $items = collect($result['items']);

        $this->bird_count = $computed['bird_count'];
        $this->total_nests = $computed['total_nests'];
        $this->nests_per_line = $computed['nests_per_line'] ?? 0;
        $this->back_fans_count = $computed['back_fans_count'];
        $this->cooling_units = $computed['cooling_units'];
        $this->windows_count = $computed['windows_count'];
        $this->side_fans_count = $computed['side_fans_count'];
        $this->heaters_count = $computed['heaters_count'];
        $this->pricing_snapshot = $result;

        $this->concrete_cost = $items->firstWhere('key', 'concrete')['total_price'] ?? 0;
        $this->steel_cost = $items->firstWhere('key', 'steel')['total_price'] ?? 0;
        $this->walls_cost = $items->firstWhere('key', 'walls')['total_price'] ?? 0;
        $this->tanks_cost = $items->firstWhere('key', 'tanks')['total_price'] ?? 0;
        $this->battery_cost = $items->firstWhere('key', 'battery')['total_price'] ?? 0;
        $this->back_fans_cost = $items->firstWhere('key', 'main_fans')['total_price'] ?? 0;
        $this->cooling_cost = $items->firstWhere('key', 'cooling')['total_price'] ?? 0;
        $this->windows_cost = $items->firstWhere('key', 'windows')['total_price'] ?? 0;
        $this->side_fans_cost = $items->firstWhere('key', 'side_fans')['total_price'] ?? 0;
        $this->heaters_cost = $items->firstWhere('key', 'heaters')['total_price'] ?? 0;
        $this->control_cost = $items->firstWhere('key', 'control')['total_price'] ?? 0;
        $this->subtotal = $result['subtotal'];

        $vatPercentage = (float) $this->vat_percentage > 0
            ? (float) $this->vat_percentage
            : TaxResolver::percentageFor('default');

        $financial = FinancialEngine::calculateTotals(
            (float) $this->subtotal,
            0,
            0,
            $vatPercentage
        );

        $this->vat_amount = FinancialEngine::toFloat($financial['vat_amount']);
        $this->total = FinancialEngine::toFloat($financial['total']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(PoultryQuotationSnapshot::class, 'poultry_quotation_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getProjectTypeLabelAttribute(): string
    {
        return PoultryProjectType::tryFrom($this->project_type ?? '')?->labelAr() ?? $this->project_type;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.str_replace('public/', '', $this->image_path));
    }

    public function getWhatsAppShareUrlAttribute(): string
    {
        $total = number_format((float) $this->total, 0);
        $subtotal = number_format((float) $this->subtotal, 0);
        $vat = number_format((float) $this->vat_amount, 0);
        $companyName = settings('company.name_ar', 'إم آي للصناعات المعدنية');

        $lines = [
            'السلام عليكم،',
            '',
            '*عرض سعر تقديري* 🏭',
            '',
            "👤 *العميل:* {$this->client_name}",
            '📋 *النوع:* '.$this->project_type_label,
            "📐 *الأبعاد:* {$this->length} × {$this->width} × {$this->height} م",
            '🐔 *السعة:* '.number_format($this->bird_count).' طائر',
            '',
            "💰 *المجموع:* {$subtotal} ج.م",
        ];

        if ((float) $this->vat_amount > 0) {
            $lines[] = "📊 *الضريبة ({$this->vat_percentage}%):* {$vat} ج.م";
        }

        $lines[] = '';
        $lines[] = "✅ *الإجمالي النهائي:* {$total} ج.م*";
        $lines[] = '';
        $lines[] = "📎 رقم العرض: {$this->quote_number}";

        if ($this->image_url) {
            $lines[] = '';
            $lines[] = '🖼️ صورة العرض: '.$this->image_url;
        }

        $lines[] = '';
        $lines[] = "{$companyName}";
        $lines[] = '📞 للاستفسار: '.settings('company.phone', '+201026253004');

        $text = urlencode(implode("\n", $lines));

        return "https://wa.me/?text={$text}";
    }
}
