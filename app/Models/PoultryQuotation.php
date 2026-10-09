<?php

namespace App\Models;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Models\Concerns\NormalizesMoneyAttributes;
use App\Quotations\Exceptions\LayerRearingDisabledException;
use App\Quotations\QuotationTemplateResolver;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Services\PoultryHousePricingService;
use App\Services\Poultry\ProposalSnapshotFreezer;
use App\Support\FinancialEngine;
use App\Support\SiloCapacity;
use App\Support\TaxResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PoultryQuotation extends Model
{
    use HasFactory, NormalizesMoneyAttributes;

    protected $table = 'poultry_quotations';

    protected $fillable = [
        'quote_number',
        'customer_id',
        'client_name',
        'client_phone',
        'client_address',
        'client_company',
        'client_email',
        'client_country',
        'client_location',
        'client_notes',
        'project_type',
        'pricing_scope',
        'quote_type_id',
        'length',
        'width',
        'height',
        'wall_type',
        'tiers',
        'lines',
        'barns_count',
        'dead_zone',
        'service_length',
        'bird_weight_kg',
        'bird_price',
        'bird_price_usd',
        'exchange_rate',
        'internal_columns',
        'roof_type',
        'birds_per_nest',
        'manure_motor_count_id',
        'motor_power_id',
        'belts_per_line_id',
        'inner_belt_length_id',
        'outer_belt_length_id',
        'silo_capacity_id',
        'silos_count',
        'layer_specs',
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
        'include_monitor',
        'monitor_cost',
        'include_electricity',
        'electricity_cost',
        'subtotal',
        'vat_amount',
        'total',
        'vat_percentage',
        'status',
        'issued_at',
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
        'bird_price' => 'decimal:2',
        'bird_price_usd' => 'decimal:2',
        'internal_columns' => 'integer',
        'exchange_rate' => 'decimal:1',
        'barns_count' => 'integer',
        'silos_count' => 'integer',
        'layer_specs' => 'array',
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
        'monitor_cost' => 'decimal:2',
        'electricity_cost' => 'decimal:2',
        'include_monitor' => 'boolean',
        'include_electricity' => 'boolean',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'pricing_snapshot' => 'array',
        'issued_at' => 'datetime',
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
                $quotation->quote_number = "Q-{$year}-".str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            $quotation->created_by = auth()->id() ?? $quotation->created_by;
            $quotation->project_type ??= PoultryProjectType::Broiler->value;
            $quotation->pricing_scope ??= PoultryPricingScope::FullProject->value;
            if (empty($quotation->issued_at)) {
                $quotation->issued_at = now();
            }

            $quotation->freezeQuoteTypeId();
        });

        static::saving(function (PoultryQuotation $quotation) {
            $quotation->guardBarnsCount();

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
                $quotation->syncCostsFromSnapshot();
                app(ProposalSnapshotFreezer::class)->apply($quotation);
                $quotation->syncDerivedEquipment();

                return;
            }

            if ($quotation->length > 0 && $quotation->width > 0 && $quotation->height > 0) {
                try {
                    $quotation->autoCompute();
                } catch (\Throwable) {
                    // silently fail during seeding or incomplete saves
                }
            }

            $quotation->syncDerivedEquipment();
        });
    }

    /**
     * يثبّت قالب العرض عند الإنشاء. تربية البياض المعطّلة تبقى بلا quote_type_id
     * حتى يُفعَّل العلم، والرسم عندها يرمي استثناء الـ Resolver.
     */
    protected function freezeQuoteTypeId(): void
    {
        if (! empty($this->quote_type_id)) {
            return;
        }

        try {
            $quoteTypeId = app(QuotationTemplateResolver::class)->quoteTypeIdFor($this);
        } catch (LayerRearingDisabledException) {
            return;
        }

        if ($quoteTypeId !== null) {
            $this->quote_type_id = $quoteTypeId;
        }
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
            'internal_columns' => (int) ($this->internal_columns ?? 0),
            'price_per_bird' => $this->resolvedBirdPriceEgp(),
            'wall_type' => $this->wall_type,
            'include_monitor' => (bool) $this->include_monitor,
            'monitor_cost' => $this->monitor_cost,
            'include_electricity' => (bool) $this->include_electricity,
            'electricity_cost' => $this->electricity_cost,
        ];

        $previous = $this->pricing_snapshot ?? [];
        $result = $service->compute($input);
        if (isset($previous['terms']) && is_array($previous['terms'])) {
            $result['terms'] = $previous['terms'];
        }
        if (isset($previous['currency']['rate_override'])) {
            $result['currency'] = is_array($result['currency'] ?? null) ? $result['currency'] : [];
            $result['currency']['rate'] = $previous['currency']['rate'];
            $result['currency']['rate_override'] = $previous['currency']['rate_override'];
        }
        $computed = $result['computed'];
        $items = collect($result['items']);

        $this->bird_count = $computed['bird_count'];
        $this->total_nests = $computed['total_nests'];
        $this->nests_per_line = $computed['nests_per_line'] ?? 0;
        $this->birds_per_nest = $result['technical']['birds_per_nest'] ?? $this->birds_per_nest;
        $this->back_fans_count = $computed['back_fans_count'];
        $this->cooling_units = $computed['cooling_units'];
        $this->windows_count = $computed['windows_count'];
        $this->side_fans_count = $computed['side_fans_count'];
        $this->heaters_count = $computed['heaters_count'];
        $this->pricing_snapshot = $result;

        $this->syncCostsFromSnapshot();

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
        app(ProposalSnapshotFreezer::class)->apply($this);
    }

    /**
     * سعة السايلو من إجمالي الطيور، وطول السير الداخلي من عرض العنبر.
     * bird_count هنا سعة العنبر الواحد، فتُضرب في عدد العنابر.
     */
    public function syncDerivedEquipment(): void
    {
        $this->syncInnerBeltFromWidth();
        $this->syncSiloFromBirds();
    }

    private function syncSiloFromBirds(): void
    {
        $birds = (int) $this->bird_count * max(1, (int) ($this->barns_count ?: 1));
        $tons = SiloCapacity::tonsForBirdCount($birds);
        if ($tons === null) {
            return;
        }

        $id = SiloCapacity::lookupIdForTons($tons);
        if ($id !== null) {
            $this->silo_capacity_id = $id;
        }

        if (! SiloCapacity::allowsSiloCountChoice($birds)) {
            $this->silos_count = 1;
        }
    }

    private function syncInnerBeltFromWidth(): void
    {
        $meters = (new PoultryTechnicalCalculator)->innerBeltMetersForWidth((float) $this->width);
        if ($meters === null) {
            return;
        }

        $id = Lookup::query()
            ->where('type', Lookup::TYPE_INNER_BELT_LENGTH)
            ->where('code', (string) $meters)
            ->where('is_active', true)
            ->value('id');

        if ($id) {
            $this->inner_belt_length_id = $id;
        }
    }

    public function resolvedBirdPriceEgp(): ?float
    {
        $usd = PoultryProjectType::tryFrom((string) $this->project_type)
            ?->birdPriceUsd((int) $this->tiers);
        $rate = (float) $this->exchange_rate;

        if ($usd === null || $rate <= 0) {
            return $this->bird_price !== null ? (float) $this->bird_price : null;
        }

        $this->bird_price_usd = $usd;
        $this->bird_price = round($usd * $rate, 2);

        return (float) $this->bird_price;
    }

    public function guardBarnsCount(): void
    {
        if ($this->barns_count === null || $this->barns_count === '') {
            return;
        }

        $numeric = is_numeric($this->barns_count) ? (float) $this->barns_count : -1;
        if ($numeric < 1 || (int) $numeric != $numeric) {
            throw ValidationException::withMessages([
                'barns_count' => 'عدد العنابر يجب أن يكون عدداً صحيحاً لا يقل عن 1.',
            ]);
        }
    }

    public function syncCostsFromSnapshot(): void
    {
        $items = collect($this->pricing_snapshot['items'] ?? []);

        $this->concrete_cost = $this->totalForItemKey($items, 'concrete');
        $this->steel_cost = $this->totalForItemKey($items, 'steel');
        $this->walls_cost = $this->totalForItemKey($items, 'walls');
        $this->tanks_cost = $this->totalForItemKey($items, 'tanks');
        $this->battery_cost = $this->totalForItemKey($items, 'battery');
        $this->back_fans_cost = $this->totalForItemKey($items, 'main_fans');
        $this->cooling_cost = $this->totalForItemKey($items, 'cooling');
        $this->windows_cost = $this->totalForItemKey($items, 'windows');
        $this->side_fans_cost = $this->totalForItemKey($items, 'side_fans');
        $this->heaters_cost = $this->totalForItemKey($items, 'heaters');
        $this->control_cost = $this->totalForItemKey($items, 'control');
        $this->electricity_cost = $this->totalForItemKey($items, 'electricity');
    }

    public function costForItemKey(string $key): float
    {
        $column = match ($key) {
            'concrete' => 'concrete_cost',
            'steel' => 'steel_cost',
            'walls' => 'walls_cost',
            'tanks' => 'tanks_cost',
            'battery' => 'battery_cost',
            'main_fans' => 'back_fans_cost',
            'cooling' => 'cooling_cost',
            'windows' => 'windows_cost',
            'side_fans' => 'side_fans_cost',
            'heaters' => 'heaters_cost',
            'control' => 'control_cost',
            'electricity' => 'electricity_cost',
            default => null,
        };

        if ($column && (float) ($this->{$column} ?? 0) > 0) {
            return (float) $this->{$column};
        }

        return $this->totalForItemKey(collect($this->pricing_snapshot['items'] ?? []), $key);
    }

    protected function totalForItemKey(\Illuminate\Support\Collection $items, string $key): float
    {
        $item = $items->firstWhere('key', $key);

        return (float) ($item['total_price'] ?? 0);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quoteType(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'quote_type_id');
    }

    public function manureMotorCount(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'manure_motor_count_id');
    }

    public function motorPower(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'motor_power_id');
    }

    public function beltsPerLine(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'belts_per_line_id');
    }

    public function innerBeltLength(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'inner_belt_length_id');
    }

    public function outerBeltLength(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'outer_belt_length_id');
    }

    public function siloCapacity(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'silo_capacity_id');
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
        $welcome = app(\App\Services\Poultry\PoultryWelcomeWhatsApp::class);

        return $welcome->link($this)
            ?? 'https://wa.me/?text='.urlencode($welcome->message($this));
    }
}
