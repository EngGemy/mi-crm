<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMoneyAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, NormalizesMoneyAttributes;

    protected $fillable = [
        'payment_number', 'contract_id', 'milestone_id',
        'description', 'percentage',
        'expected_amount', 'paid_amount', 'currency',
        'due_date', 'paid_date',
        'status', 'payment_method',
        'reference_number', 'bank_name', 'attachments', 'notes',
        'received_by', 'sort_order',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'attachments' => 'array',
    ];

    public const STATUSES = [
        'pending' => 'قيد الانتظار',
        'paid' => 'مدفوعة',
        'partial' => 'مدفوعة جزئياً',
        'overdue' => 'متأخرة',
        'cancelled' => 'ملغية',
        'refunded' => 'مستردة',
    ];

    public const PAYMENT_METHODS = [
        'bank_transfer' => 'تحويل بنكي',
        'cash' => 'نقدي',
        'cheque' => 'شيك',
        'credit_card' => 'بطاقة ائتمان',
        'other' => 'أخرى',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->payment_number)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $payment->payment_number = "PAY-{$year}-".str_pad($count, 5, '0', STR_PAD_LEFT);
            }

            // حساب expected_amount من النسبة لو غير محدد
            if (empty($payment->expected_amount) && $payment->contract && $payment->percentage) {
                $payment->expected_amount = (float) $payment->contract->total_value
                    * ((float) $payment->percentage / 100);
            }
        });

        static::saving(function (Payment $payment) {
            // تحديث status تلقائياً
            $paid = (float) $payment->paid_amount;
            $expected = (float) $payment->expected_amount;

            if ($paid <= 0) {
                $payment->status = $payment->due_date && $payment->due_date->isPast()
                    ? 'overdue' : 'pending';
            } elseif ($paid >= $expected) {
                $payment->status = 'paid';
                if (empty($payment->paid_date)) {
                    $payment->paid_date = now();
                }
            } else {
                $payment->status = 'partial';
            }
        });

        static::saved(function (Payment $payment) {
            $payment->contract?->refreshPaymentStatus();
        });

        static::deleted(function (Payment $payment) {
            $payment->contract?->refreshPaymentStatus();
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ContractMilestone::class, 'milestone_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->expected_amount - (float) $this->paid_amount);
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (! $this->due_date || $this->status === 'paid') {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date, false);
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['pending', 'partial', 'overdue'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    protected function expectedAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }

    protected function paidAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::normalizeMoneyFromStorage($value),
            set: fn ($value) => self::normalizeMoneyForStorage($value),
        );
    }
}
