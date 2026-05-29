<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'user_id', 'assigned_by', 'color',
        'type', 'subject', 'description',
        'duration_minutes', 'outcome', 'attachments',
        'scheduled_at', 'completed_at', 'is_completed',
    ];

    protected $casts = [
        'attachments' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public const TYPES = [
        'call' => 'مكالمة',
        'whatsapp' => 'واتساب',
        'email' => 'إيميل',
        'sms' => 'SMS',
        'visit' => 'زيارة',
        'meeting' => 'اجتماع',
        'note' => 'ملاحظة',
        'status_change' => 'تغيير حالة',
        'reminder' => 'تذكير',
    ];

    public const OUTCOMES = [
        'positive' => 'إيجابي',
        'neutral' => 'محايد',
        'negative' => 'سلبي',
        'no_answer' => 'لم يرد',
        'rescheduled' => 'تم إعادة الجدولة',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public const TYPE_COLORS = [
        'call'     => '#22c55e',
        'whatsapp' => '#25D366',
        'email'    => '#f97316',
        'sms'      => '#eab308',
        'visit'    => '#8b5cf6',
        'meeting'  => '#3b82f6',
        'note'     => '#6b7280',
        'reminder' => '#ef4444',
        'status_change' => '#64748b',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($activity) {
            if (in_array($activity->type, ['call', 'whatsapp', 'email', 'visit', 'meeting'])) {
                $activity->lead->update([
                    'last_contact_at' => now(),
                ]);
            }
        });
    }
}
