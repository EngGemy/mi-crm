<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMoneyAttributes;
use App\Support\FinancialEngine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, NormalizesMoneyAttributes, SoftDeletes;

    protected $fillable = [
        'contract_number', 'project_code',
        'customer_id', 'contract_type_id', 'quotation_id', 'created_by', 'approved_by',
        'contract_date', 'project_name', 'project_description', 'installation_location',
        'hall_length', 'hall_width', 'hall_height', 'hall_count',
        'cage_count', 'bird_capacity', 'technical_specs',
        'cages_cost', 'construction_cost', 'electricity_cost', 'plumbing_cost',
        'accessories_cost', 'other_cost', 'subtotal',
        'discount_amount', 'discount_percentage',
        'vat_percentage', 'vat_amount', 'total_value',
        'currency', 'exchange_rate',
        'manufacturing_days', 'manufacturing_start_date',
        'expected_delivery_date', 'actual_delivery_date',
        'warranty_start_date', 'warranty_end_date',
        'warranty_months', 'manufacturing_warranty_years',
        'status', 'payment_status',
        'preamble_content', 'custom_terms', 'additional_data',
        'signed_pdf_path', 'attachments', 'internal_notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'manufacturing_start_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'discount_percentage' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'technical_specs' => 'array',
        'additional_data' => 'array',
        'attachments' => 'array',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'pending_approval' => 'قيد الموافقة',
        'approved' => 'معتمد',
        'signed' => 'موقّع',
        'manufacturing' => 'قيد التصنيع',
        'shipping' => 'قيد الشحن',
        'installing' => 'قيد التركيب',
        'testing' => 'قيد التشغيل التجريبي',
        'completed' => 'مكتمل',
        'on_hold' => 'معلّق',
        'cancelled' => 'ملغي',
        'archived' => 'مؤرشف',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'غير مدفوع',
        'partially_paid' => 'مدفوع جزئياً',
        'paid' => 'مدفوع بالكامل',
        'overdue' => 'متأخر',
    ];

    /**
     * توليد أرقام تلقائية + حساب القيم
     */
    protected static function booted(): void
    {
        static::creating(function (Contract $contract) {
            if (empty($contract->contract_number)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->withTrashed()->count() + 1;
                $contract->contract_number = "CTR-{$year}-".str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            if (empty($contract->project_code)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->withTrashed()->count() + 1;
                $contract->project_code = "PRJ-{$year}-".str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (Contract $contract) {
            // إذا كان العقد مُنشأ من عرض ويوجد snapshot مالي، نقرأ منه مباشرة
            $quotation = $contract->quotation;
            if ($quotation && ! empty($quotation->pricing_snapshot['financial'])) {
                $financial = $quotation->pricing_snapshot['financial'];
                $contract->subtotal = FinancialEngine::toFloat($financial['subtotal']);
                $contract->discount_amount = FinancialEngine::toFloat($financial['discount_amount']);
                $contract->vat_amount = FinancialEngine::toFloat($financial['vat_amount']);
                $contract->total_value = FinancialEngine::toFloat($financial['total']);
            } else {
                // حساب الإجمالي تلقائياً (عقد يدوي)
                $subtotal = (float) $contract->cages_cost
                    + (float) $contract->construction_cost
                    + (float) $contract->electricity_cost
                    + (float) $contract->plumbing_cost
                    + (float) $contract->accessories_cost
                    + (float) $contract->other_cost;

                $financial = FinancialEngine::calculateTotals(
                    $subtotal,
                    (float) $contract->discount_percentage,
                    (float) $contract->discount_amount,
                    (float) $contract->vat_percentage
                );

                $contract->subtotal = FinancialEngine::toFloat($financial['subtotal']);
                $contract->discount_amount = FinancialEngine::toFloat($financial['discount_amount']);
                $contract->vat_amount = FinancialEngine::toFloat($financial['vat_amount']);
                $contract->total_value = FinancialEngine::toFloat($financial['total']);
            }

            // تاريخ التسليم تلقائياً
            if ($contract->contract_date && $contract->manufacturing_days && ! $contract->expected_delivery_date) {
                $contract->expected_delivery_date = Carbon::parse($contract->contract_date)
                    ->addDays((int) $contract->manufacturing_days);
            }

            // تاريخ بداية الضمان
            if ($contract->actual_delivery_date && ! $contract->warranty_start_date) {
                $contract->warranty_start_date = $contract->actual_delivery_date;
                $contract->warranty_end_date = Carbon::parse($contract->actual_delivery_date)
                    ->addMonths((int) $contract->warranty_months);
            }
        });
    }

    // =================== Relationships ===================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('sort_order');
    }

    public function clauses(): BelongsToMany
    {
        return $this->belongsToMany(ContractClause::class, 'contract_clause_attachments')
            ->using(ContractClauseAttachment::class)
            ->withPivot([
                'id', 'content_override', 'variables_values', 'items',
                'sort_order', 'is_visible', 'notes',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function clauseAttachments(): HasMany
    {
        return $this->hasMany(ContractClauseAttachment::class)->orderBy('sort_order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('sort_order');
    }

    public function changeLogs(): MorphMany
    {
        return $this->morphMany(ChangeLog::class, 'subject');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =================== Computed Attributes ===================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('paid_amount');
    }

    public function getTotalDueAttribute(): float
    {
        return max(0, (float) $this->total_value - $this->total_paid);
    }

    public function getCollectionPercentageAttribute(): float
    {
        if ((float) $this->total_value <= 0) {
            return 0;
        }

        return ($this->total_paid / (float) $this->total_value) * 100;
    }

    public function getDaysUntilDeliveryAttribute(): ?int
    {
        if (! $this->expected_delivery_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expected_delivery_date, false);
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->expected_delivery_date
            && $this->expected_delivery_date->isPast()
            && ! in_array($this->status, ['completed', 'cancelled', 'archived']);
    }

    /**
     * تحديث حالة الدفع تلقائياً
     */
    public function refreshPaymentStatus(): void
    {
        $totalPaid = $this->total_paid;
        $totalValue = (float) $this->total_value;

        if ($totalPaid <= 0) {
            $hasOverdue = $this->payments()
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->exists();
            $newStatus = $hasOverdue ? 'overdue' : 'unpaid';
        } elseif ($totalPaid >= $totalValue) {
            $newStatus = 'paid';
        } else {
            $hasOverdue = $this->payments()
                ->whereIn('status', ['pending', 'partial'])
                ->where('due_date', '<', now())
                ->exists();
            $newStatus = $hasOverdue ? 'overdue' : 'partially_paid';
        }

        if ($this->payment_status !== $newStatus) {
            $this->update(['payment_status' => $newStatus]);
        }
    }

    protected function cagesCost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function constructionCost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function electricityCost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function plumbingCost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function accessoriesCost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function otherCost(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function discountAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function vatAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function totalValue(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }
}
