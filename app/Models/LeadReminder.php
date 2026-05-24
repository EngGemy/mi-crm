<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'user_id', 'title', 'description', 'type',
        'remind_at', 'status', 'completed_at', 'completed_by',
        'snoozed_until', 'snooze_count', 'notified', 'notified_at',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'completed_at' => 'datetime',
        'snoozed_until' => 'datetime',
        'notified_at' => 'datetime',
        'notified' => 'boolean',
        'snooze_count' => 'integer',
    ];

    public const TYPES = [
        'call' => 'مكالمة',
        'visit' => 'زيارة',
        'email' => 'إيميل',
        'whatsapp' => 'واتساب',
        'meeting' => 'اجتماع',
        'other' => 'أخرى',
    ];

    public const STATUSES = [
        'pending' => 'في الانتظار',
        'completed' => 'مكتمل',
        'snoozed' => 'مؤجل',
        'cancelled' => 'ملغي',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
            ->where('remind_at', '<=', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
