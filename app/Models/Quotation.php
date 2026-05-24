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
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, NormalizesMoneyAttributes, SoftDeletes;

    protected $table = 'quotations';

    protected $fillable = [
        'quotation_number', 'revision_number', 'parent_quotation_id',
        'customer_id', 'quotation_type_id',
        'created_by', 'approved_by',
        'status',
        'quotation_date', 'valid_until', 'validity_period_days',
        'project_name', 'project_description', 'installation_location',
        'hall_type', 'hall_length', 'hall_width', 'hall_height',
        'hall_count', 'cage_count', 'bird_capacity', 'average_weight_kg',
        'tiers', 'lines', 'dead_zone_meters', 'side_fans_count', 'heaters_count',
        'back_fans_count', 'cooling_units', 'windows_count', 'pricing_snapshot',
        'language', 'currency', 'exchange_rate',
        'subtotal', 'discount_percentage', 'discount_amount',
        'vat_percentage', 'vat_amount',
        'total_amount', 'total_amount_secondary', 'secondary_currency',
        'notes', 'internal_notes',
        'contract_id', 'converted_at',
        'sent_at', 'approved_at', 'rejected_at',
        'attachments',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'validity_period_days' => 'integer',
        'hall_length' => 'decimal:2',
        'hall_width' => 'decimal:2',
        'hall_height' => 'decimal:2',
        'hall_count' => 'integer',
        'cage_count' => 'integer',
        'bird_capacity' => 'integer',
        'average_weight_kg' => 'decimal:2',
        'dead_zone_meters' => 'decimal:2',
        'cooling_units' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'tiers' => 'integer',
        'lines' => 'integer',
        'side_fans_count' => 'integer',
        'heaters_count' => 'integer',
        'back_fans_count' => 'integer',
        'windows_count' => 'integer',
        'pricing_snapshot' => 'array',
        'discount_percentage' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'converted_at' => 'datetime',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'attachments' => 'array',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'sent' => 'مرسل',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'expired' => 'منتهي الصلاحية',
        'converted' => 'محوّل لعقد',
    ];

    public const HALL_TYPES = [
        'تسمين' => 'تسمين',
        'بياض' => 'بياض',
        'تربية' => 'تربية',
        'أمهات' => 'أمهات',
    ];

    public const LANGUAGES = [
        'ar' => 'العربية',
        'en' => 'الإنجليزية',
        'both' => 'العربية + الإنجليزية',
    ];

    public const CURRENCIES = [
        'EGP' => 'جنيه مصري',
        'USD' => 'دولار أمريكي',
        'SAR' => 'ريال سعودي',
        'AED' => 'درهم إماراتي',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {
            if (empty($quotation->quotation_number)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->withTrashed()->count() + 1;
                $quotation->quotation_number = "QT-{$year}-".str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            if (empty($quotation->valid_until) && $quotation->quotation_date && $quotation->validity_period_days) {
                $quotation->valid_until = Carbon::parse($quotation->quotation_date)
                    ->addDays((int) $quotation->validity_period_days);
            }
        });

        static::saving(function (Quotation $quotation) {
            // إذا وجد snapshot مالي (عرض دواجن محفوظ)، نقرأ الأرقام منه مباشرة
            $snapshot = $quotation->pricing_snapshot ?? [];
            if (! empty($snapshot['financial'])) {
                $financial = $snapshot['financial'];
                $quotation->subtotal = FinancialEngine::toFloat($financial['subtotal']);
                $quotation->discount_amount = FinancialEngine::toFloat($financial['discount_amount']);
                $quotation->vat_amount = FinancialEngine::toFloat($financial['vat_amount']);
                $quotation->total_amount = FinancialEngine::toFloat($financial['total']);
                if ($financial['total_secondary'] !== null) {
                    $quotation->total_amount_secondary = FinancialEngine::toFloat($financial['total_secondary']);
                }

                return;
            }

            // لو في items محفوظة في DB، نحسب منهم (عروض يدوية)
            if ($quotation->exists) {
                $subtotal = (float) $quotation->items()->sum('total_price') ?? 0;
                if ($subtotal > 0) {
                    $quotation->subtotal = $subtotal;
                }
            }

            $financial = FinancialEngine::calculateTotals(
                (float) $quotation->subtotal,
                (float) $quotation->discount_percentage,
                (float) $quotation->discount_amount,
                (float) $quotation->vat_percentage,
                ($quotation->secondary_currency && (float) $quotation->exchange_rate > 0)
                    ? (float) $quotation->exchange_rate
                    : null
            );

            $quotation->subtotal = FinancialEngine::toFloat($financial['subtotal']);
            $quotation->discount_amount = FinancialEngine::toFloat($financial['discount_amount']);
            $quotation->vat_amount = FinancialEngine::toFloat($financial['vat_amount']);
            $quotation->total_amount = FinancialEngine::toFloat($financial['total']);
            if ($financial['total_secondary'] !== null) {
                $quotation->total_amount_secondary = FinancialEngine::toFloat($financial['total_secondary']);
            }
        });
    }

    // =================== Relationships ===================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotationType(): BelongsTo
    {
        return $this->belongsTo(QuotationType::class);
    }

    public function parentQuotation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quotation_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_quotation_id')->orderBy('revision_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function sectionAttachments(): HasMany
    {
        return $this->hasMany(QuotationSectionAttachment::class)->orderBy('sort_order');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(QuotationSection::class, 'quotation_section_attachments')
            ->withPivot([
                'content_override_ar',
                'content_override_en',
                'custom_images',
                'sort_order',
                'is_visible',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function technicalSpecs(): HasMany
    {
        return $this->hasMany(QuotationTechnicalSpec::class)->orderBy('sort_order');
    }

    public function termAttachments(): HasMany
    {
        return $this->hasMany(QuotationTermAttachment::class)->orderBy('sort_order');
    }

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(QuotationTerm::class, 'quotation_term_attachments')
            ->withPivot([
                'content_override',
                'variables_values',
                'sort_order',
                'is_visible',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(QuotationImage::class)->orderBy('sort_order');
    }

    // =================== Computed Attributes ===================

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getHallTypeLabelAttribute(): ?string
    {
        return self::HALL_TYPES[$this->hall_type] ?? $this->hall_type;
    }

    public function getLanguageLabelAttribute(): string
    {
        return self::LANGUAGES[$this->language] ?? $this->language;
    }

    public function getCurrencyLabelAttribute(): string
    {
        return self::CURRENCIES[$this->currency] ?? $this->currency;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->valid_until) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->valid_until, false);
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

    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function totalAmountSecondary(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value, true),
        );
    }
}
