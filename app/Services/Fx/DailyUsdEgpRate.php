<?php

namespace App\Services\Fx;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * سعر الدولار مقابل الجنيه من مصادر السعر العالمي اليومية.
 *
 * المصدر الأول ExchangeRate-API (تحديث يومي)، والبديل Currency-API.
 */
class DailyUsdEgpRate
{
    /** @return array{rate: float, source: string, as_of: ?string} */
    public function current(): array
    {
        $cached = Cache::get($this->cacheKey());
        if ($this->valid($cached)) {
            return $cached;
        }

        $fetched = $this->fetch();
        if ($fetched !== null) {
            Cache::put($this->cacheKey(), $fetched, now()->endOfDay());
            $this->persist($fetched);

            return $fetched;
        }

        return $this->fallback();
    }

    public function rate(): float
    {
        return $this->current()['rate'];
    }

    /** @return array{rate: float, source: string, as_of: ?string} */
    public function refresh(): array
    {
        Cache::forget($this->cacheKey());

        return $this->current();
    }

    /** @return array{rate: float, source: string, as_of: ?string}|null */
    private function fetch(): ?array
    {
        if (! config('services.fx.daily_fetch', true)) {
            return null;
        }

        return $this->fromExchangeRateApi() ?? $this->fromCurrencyApi();
    }

    /** @return array{rate: float, source: string, as_of: ?string}|null */
    private function fromExchangeRateApi(): ?array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://open.er-api.com/v6/latest/USD');
        } catch (Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        return $this->quote(
            $response->json('rates.EGP'),
            'ExchangeRate-API',
            $response->json('time_last_update_utc'),
        );
    }

    /** @return array{rate: float, source: string, as_of: ?string}|null */
    private function fromCurrencyApi(): ?array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.min.json');
        } catch (Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        return $this->quote(
            $response->json('usd.egp'),
            'Currency-API',
            $response->json('date'),
        );
    }

    /** @return array{rate: float, source: string, as_of: ?string}|null */
    private function quote(mixed $rate, string $source, mixed $asOf): ?array
    {
        if (! is_numeric($rate) || (float) $rate <= 1) {
            return null;
        }

        return [
            'rate' => round((float) $rate, 4),
            'source' => $source,
            'as_of' => is_string($asOf) && $asOf !== '' ? $asOf : now()->toDateString(),
        ];
    }

    /** @return array{rate: float, source: string, as_of: ?string} */
    private function fallback(): array
    {
        $saved = 48.0;
        try {
            $saved = (float) settings('poultry_pricing.egp_to_usd_rate', settings('defaults.exchange_rate', 48));
        } catch (Throwable) {
            $saved = 48.0;
        }

        return [
            'rate' => $saved > 1 ? $saved : 48.0,
            'source' => 'الإعدادات المحفوظة',
            'as_of' => null,
        ];
    }

    /** @param  array{rate: float, source: string, as_of: ?string}  $quote */
    private function persist(array $quote): void
    {
        try {
            $current = (float) settings('poultry_pricing.egp_to_usd_rate', 0);
            if (abs($current - $quote['rate']) < 0.0001) {
                return;
            }

            app(SettingsService::class)->set(
                'poultry_pricing.egp_to_usd_rate',
                $quote['rate'],
                null,
                'تحديث يومي لسعر الدولار العالمي من '.$quote['source'],
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function valid(mixed $cached): bool
    {
        return is_array($cached)
            && isset($cached['rate'], $cached['source'])
            && is_numeric($cached['rate'])
            && (float) $cached['rate'] > 1;
    }

    private function cacheKey(): string
    {
        return 'fx.usd_egp.'.now()->toDateString();
    }
}
