<?php

namespace App\Observers;

use App\Models\ChangeLog;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * يسجل أي تغيير على العقد في جدول change_logs
 * مهم للمحاسبة والمراجعات الداخلية
 */
class ContractObserver
{
    public function created(Contract $contract): void
    {
        $this->log($contract, 'created', null, $contract->getAttributes());
    }

    public function updated(Contract $contract): void
    {
        $changes = $contract->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $original = collect($changes)
            ->mapWithKeys(fn ($v, $k) => [$k => $contract->getOriginal($k)])
            ->toArray();

        $event = match (true) {
            isset($changes['status']) && $changes['status'] === 'signed' => 'signed',
            isset($changes['status']) && $changes['status'] === 'approved' => 'approved',
            isset($changes['status']) && $changes['status'] === 'cancelled' => 'cancelled',
            isset($changes['status']) && $changes['status'] === 'completed' => 'delivered',
            default => 'updated',
        };

        $this->log($contract, $event, $original, $changes);
    }

    public function deleted(Contract $contract): void
    {
        $this->log($contract, 'deleted', $contract->getAttributes(), null);
    }

    public function restored(Contract $contract): void
    {
        $this->log($contract, 'restored', null, $contract->getAttributes());
    }

    /**
     * تسجيل في ChangeLog
     */
    protected function log(Contract $contract, string $event, ?array $old, ?array $new): void
    {
        ChangeLog::create([
            'subject_type' => Contract::class,
            'subject_id' => $contract->id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'event' => $event,
            'old_values' => $this->sanitize($old),
            'new_values' => $this->sanitize($new),
            'changed_fields' => $new ? array_keys($new) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * تنظيف القيم الحساسة
     */
    protected function sanitize(?array $data): ?array
    {
        if (! $data) {
            return null;
        }

        return collect($data)
            ->except(['updated_at', 'created_at', 'remember_token', 'password'])
            ->toArray();
    }
}
