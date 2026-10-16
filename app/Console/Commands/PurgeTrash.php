<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\PoultryQuotation;
use App\Models\Quotation;
use Illuminate\Console\Command;

class PurgeTrash extends Command
{
    protected $signature = 'trash:purge {--days=}';

    protected $description = 'يمسح نهائي العناصر اللي عدّت مدة سلة المحذوفات';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('trash.retention_days', 30));
        $days = max(1, $days);
        $cutoff = now()->subDays($days);
        $removed = 0;

        foreach ([Lead::class, Customer::class, PoultryQuotation::class, Quotation::class, Contract::class] as $class) {
            $class::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(100, function ($rows) use (&$removed): void {
                    foreach ($rows as $row) {
                        try {
                            $row->forceDelete();
                            $removed++;
                        } catch (\Throwable) {
                            // علاقة مانعة للحذف النهائي تفضل في السلة لحد ما تتفك
                        }
                    }
                });
        }

        $this->info("تم الحذف النهائي لعدد {$removed} بعد {$days} يوم.");

        return self::SUCCESS;
    }
};
