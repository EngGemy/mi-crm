<?php

namespace App\Support;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Helper تسجيل صريح للأحداث غير-Eloquent (دخول/خروج، تصدير، تنزيل، تحويل، إلخ).
 *
 * الاستخدام: AuditLogger::log($subject, 'downloaded', ['file' => 'contract.pdf']);
 */
class AuditLogger
{
    /**
     * تسجيل حدث في سجل التدقيق.
     *
     * @param  Model|object|string|null  $subject  المورد المتأثر (موديل، أو اسم فئة، أو null)
     * @param  string  $event  أحداث ChangeLog::EVENTS
     * @param  array<string, mixed>  $context  بيانات إضافية تُخزَّن في new_values
     * @param  string|null  $reason  سبب الحدث
     */
    public static function log(object|string|null $subject, string $event, array $context = [], ?string $reason = null): void
    {
        try {
            $subjectType = is_object($subject) ? get_class($subject) : $subject;
            $subjectId = $subject instanceof Model ? $subject->getKey() : null;

            ChangeLog::create([
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'النظام',
                'event' => $event,
                'old_values' => null,
                'new_values' => $context ?: null,
                'changed_fields' => $context ? array_keys($context) : null,
                'reason' => $reason,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable) {
            // silently fail — audit must never break the main operation
        }
    }
}
