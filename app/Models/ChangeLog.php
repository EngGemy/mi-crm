<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChangeLog extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id',
        'user_id', 'user_name',
        'event', 'old_values', 'new_values', 'changed_fields',
        'reason', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
    ];

    public const EVENTS = [
        'created' => 'إنشاء',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
        'restored' => 'استرجاع',
        'signed' => 'توقيع',
        'approved' => 'اعتماد',
        'cancelled' => 'إلغاء',
        'paid' => 'دفع',
        'shipped' => 'شحن',
        'delivered' => 'تسليم',
        'login' => 'تسجيل دخول',
        'logout' => 'تسجيل خروج',
        'login_failed' => 'محاولة دخول فاشلة',
        'exported' => 'تصدير',
        'imported' => 'استيراد',
        'downloaded' => 'تنزيل',
        'converted' => 'تحويل',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENTS[$this->event] ?? $this->event;
    }
}
