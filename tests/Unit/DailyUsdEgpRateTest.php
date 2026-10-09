<?php

namespace Tests\Unit;

use App\Services\Fx\DailyUsdEgpRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DailyUsdEgpRateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.fx.daily_fetch' => true]);
        Cache::flush();
    }

    public function test_daily_rate_comes_from_the_global_exchange_feed(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Thu, 09 Oct 2026 00:02:31 +0000',
                'rates' => ['EGP' => 48.73],
            ]),
        ]);

        $quote = (new DailyUsdEgpRate)->current();

        $this->assertSame(48.73, $quote['rate']);
        $this->assertSame('ExchangeRate-API', $quote['source']);
        $this->assertSame('Thu, 09 Oct 2026 00:02:31 +0000', $quote['as_of']);
        Http::assertSentCount(1);
    }

    public function test_second_source_is_used_when_the_first_feed_fails(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([], 503),
            'cdn.jsdelivr.net/*' => Http::response([
                'date' => '2026-10-09',
                'usd' => ['egp' => 49.15],
            ]),
        ]);

        $quote = (new DailyUsdEgpRate)->current();

        $this->assertSame(49.15, $quote['rate']);
        $this->assertSame('Currency-API', $quote['source']);
        $this->assertSame('2026-10-09', $quote['as_of']);
    }

    public function test_saved_setting_is_used_when_both_feeds_fail(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([], 500),
            'cdn.jsdelivr.net/*' => Http::response(['usd' => ['egp' => 0]]),
        ]);

        $quote = (new DailyUsdEgpRate)->current();

        $this->assertSame(48.0, $quote['rate']);
        $this->assertSame('الإعدادات المحفوظة', $quote['source']);
    }
}
