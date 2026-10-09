<?php

namespace App\Console\Commands;

use App\Services\Fx\DailyUsdEgpRate;
use Illuminate\Console\Command;

class RefreshUsdEgpRate extends Command
{
    protected $signature = 'fx:refresh-usd-egp';

    protected $description = 'تحديث سعر الدولار مقابل الجنيه من مصدر السعر العالمي اليومي';

    public function handle(DailyUsdEgpRate $rates): int
    {
        $quote = $rates->refresh();
        $when = $quote['as_of'] ? ' ('.$quote['as_of'].')' : '';
        $this->info('1 USD = '.$quote['rate'].' EGP — '.$quote['source'].$when);

        return self::SUCCESS;
    }
}
