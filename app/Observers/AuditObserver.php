<?php

namespace App\Observers;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * مراقب تدقيق عام — يسجّل كل أحداث CRUD لكل الموديلات المُدرجة في config/audit.php.
 *
 * - يستبعد الحقول الحساسة/الضخمة المُعرَّفة في الإعدادات.
 * - يستنتج أحداث الحالة (signed/approved/cancelled/delivered) من تغيّر status.
 * - لا يكسر العملية الأصلية أبدًا (كل التسجيل داخل try/catch).
 */
class AuditObserver
{
    /** @var bool يمنع التسجيل المتتالي الناتج عن تعديلات داخلية */
    protected static bool $logging = false;

    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        if (static::$logging) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $original = collect($changes)
            ->mapWithKeys(fn ($v, $k) => [$k => $model->getOriginal($k)])
            ->toArray();

        $event = $this->inferEvent($changes);

        $this->log($model, $event, $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getAttributes(), null);
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored', null, $model->getAttributes());
    }

    /**
     * استنتاج الحدث من تغيّر الحقول (خاصة status).
     */
    protected function inferEvent(array $changes): string
    {
        if (! isset($changes['status'])) {
            return 'updated';
        }

        return match ($changes['status']) {
            'signed' => 'signed',
            'approved' => 'approved',
            'cancelled' => 'cancelled',
            'completed' => 'delivered',
            default => 'updated',
        };
    }

    /**
     * كتابة السجل في change_logs — داخل try/catch حتى لا تكسر العملية الأصلية.
     */
    protected function log(Model $model, string $event, ?array $old, ?array $new): void
    {
        try {
            static::$logging = true;

            $exclude = array_merge(
                config('audit.global_exclude', []),
                config('audit.per_model_exclude.'.get_class($model), [])
            );

            ChangeLog::create([
                'subject_type' => get_class($model),
                'subject_id' => $model->getKey(),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'النظام',
                'event' => $event,
                'old_values' => $this->sanitize($old, $exclude),
                'new_values' => $this->sanitize($new, $exclude),
                'changed_fields' => $new ? array_keys($new) : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable) {
            // silently fail — audit must never break the main operation
        } finally {
            static::$logging = false;
        }
    }

    /**
     * تنظيف القيم: استبعاد حقول + تقصير قيم ضخمة.
     */
    protected function sanitize(?array $data, array $exclude): ?array
    {
        if (! $data) {
            return null;
        }

        $maxLen = config('audit.max_value_length', 2000);

        return collect($data)
            ->except($exclude)
            ->map(function ($value) use ($maxLen) {
                if (is_string($value) && mb_strlen($value) > $maxLen) {
                    return mb_substr($value, 0, $maxLen).'…';
                }

                return $value;
            })
            ->toArray();
    }
}
