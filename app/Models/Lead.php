<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_number', 'name', 'phone', 'whatsapp', 'email',
        'company', 'position', 'country', 'city', 'address',
        'project_type', 'project_size', 'estimated_budget',
        'expected_close_date', 'source', 'source_details',
        'status', 'score', 'priority',
        'assigned_to', 'created_by',
        'customer_id', 'quotation_id', 'contract_id', 'converted_at',
        'lost_reason', 'lost_notes', 'lost_at',
        'last_contact_at', 'next_followup_at',
        'tags', 'notes',
    ];

    protected $casts = [
        'estimated_budget' => 'decimal:2',
        'expected_close_date' => 'date',
        'converted_at' => 'datetime',
        'lost_at' => 'datetime',
        'last_contact_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'tags' => 'array',
        'score' => 'integer',
    ];

    public const STATUSES = [
        'new' => 'جديد',
        'contacted' => 'تم التواصل',
        'qualified' => 'مؤهل',
        'opportunity' => 'فرصة',
        'won' => 'تم الإغلاق',
        'lost' => 'مفقود',
    ];

    public const STATUS_COLORS = [
        'new' => 'gray',
        'contacted' => 'info',
        'qualified' => 'warning',
        'opportunity' => 'primary',
        'won' => 'success',
        'lost' => 'danger',
    ];

    public const SOURCES = [
        'facebook' => 'فيسبوك',
        'whatsapp' => 'واتساب',
        'instagram' => 'إنستجرام',
        'website' => 'الموقع الإلكتروني',
        'referral' => 'ترشيح',
        'walk_in' => 'زيارة مباشرة',
        'phone_call' => 'مكالمة هاتفية',
        'exhibition' => 'معرض',
        'cold_call' => 'مكالمة باردة',
        'other' => 'أخرى',
    ];

    public const PRIORITIES = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'urgent' => 'عاجلة',
    ];

    public const LOST_REASONS = [
        'price' => 'السعر',
        'timing' => 'التوقيت',
        'competitor' => 'منافس',
        'no_response' => 'عدم الرد',
        'no_budget' => 'عدم وجود ميزانية',
        'not_interested' => 'غير مهتم',
        'wrong_fit' => 'غير مناسب',
        'other' => 'أخرى',
    ];

    // ============ Relationships ============

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at', 'desc');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(LeadReminder::class)->orderBy('remind_at');
    }

    public function pendingReminders(): HasMany
    {
        return $this->hasMany(LeadReminder::class)
            ->where('status', 'pending')
            ->orderBy('remind_at');
    }

    // ============ Scopes ============

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['won', 'lost']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeNeedsFollowup($query)
    {
        return $query->where('next_followup_at', '<=', now())
            ->whereNotIn('status', ['won', 'lost']);
    }

    public function scopeHotLeads($query)
    {
        return $query->where('score', '>=', 70)
            ->whereIn('status', ['qualified', 'opportunity']);
    }

    // ============ Boot ============

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {
            if (empty($lead->lead_number)) {
                $lead->lead_number = self::generateLeadNumber();
            }
            if (empty($lead->created_by)) {
                $lead->created_by = auth()->id();
            }
        });
    }

    public static function generateLeadNumber(): string
    {
        $year = now()->format('Y');
        $count = self::whereYear('created_at', $year)->withTrashed()->count() + 1;

        return sprintf('LEAD-%s-%04d', $year, $count);
    }

    // ============ Helpers ============

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getIsHotAttribute(): bool
    {
        return $this->score >= 70 && in_array($this->status, ['qualified', 'opportunity']);
    }

    public function getDaysSinceLastContactAttribute(): int
    {
        if (! $this->last_contact_at) {
            return 999;
        }

        return $this->last_contact_at->diffInDays(now());
    }
}
