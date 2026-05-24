<?php

namespace App\Services;

use App\Models\CompanyBankAccount;
use App\Models\Setting;
use App\Models\SettingHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'app_settings';

    protected const CACHE_TTL = 86400; // 24 hours

    /**
     * Get a setting value by key with fallback
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    /**
     * Get all settings (cached)
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::all()
                ->mapWithKeys(fn ($s) => [$s->key => $this->castValue($s)])
                ->toArray();
        });
    }

    /**
     * Get all settings in a category
     */
    public function category(string $category): array
    {
        return collect($this->all())
            ->filter(fn ($v, $k) => str_starts_with($k, "$category."))
            ->toArray();
    }

    /**
     * Set a setting value (with audit trail)
     */
    public function set(string $key, $value, ?int $userId = null, ?string $reason = null): void
    {
        $setting = Setting::where('key', $key)->first();
        if (! $setting) {
            throw new \Exception("Setting [$key] not found");
        }

        $oldValue = $setting->value;
        $newValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;

        // Audit trail
        SettingHistory::create([
            'setting_id' => $setting->id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $userId ?? auth()->id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'reason' => $reason,
            'created_at' => now(),
        ]);

        $setting->update([
            'value' => $newValue,
            'updated_by' => $userId ?? auth()->id(),
        ]);

        $this->clearCache();
    }

    /**
     * Cast value to proper type based on setting type
     */
    protected function castValue(Setting $setting): mixed
    {
        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => in_array(strtolower((string) $setting->value), ['1', 'true', 'yes', 'on'], true),
            'json', 'array' => json_decode($setting->value, true) ?? [],
            'date' => $setting->value ? Carbon::parse($setting->value) : null,
            default => $setting->value,
        };
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // =================== Convenience Helpers ===================

    public function companyName(string $lang = 'ar'): string
    {
        return $this->get("company.name_$lang", 'Company Name');
    }

    public function tagline(string $lang = 'ar'): string
    {
        return $this->get("company.tagline_$lang", '');
    }

    public function logo(string $type = 'main'): string
    {
        return $this->get("branding.logo_$type", 'images/default-logo.png');
    }

    public function primaryColor(): string
    {
        return $this->get('branding.primary_color', '#C00000');
    }

    public function secondaryColor(): string
    {
        return $this->get('branding.secondary_color', '#2B2B2B');
    }

    public function phones(): array
    {
        return $this->get('contact.phones', []);
    }

    public function defaultBankAccount(): ?CompanyBankAccount
    {
        return CompanyBankAccount::where('is_default', true)->where('is_active', true)->first();
    }

    public function address(string $lang = 'ar'): string
    {
        return $this->get("contact.address_$lang", '');
    }

    public function vatPercentage(): float
    {
        return (float) $this->get('legal.default_vat_percentage', 14);
    }

    public function manufacturingDays(): int
    {
        return (int) $this->get('defaults.manufacturing_days', 105);
    }

    public function warrantyMonths(): int
    {
        return (int) $this->get('defaults.warranty_months', 12);
    }

    public function warrantyYearsSteel(): int
    {
        return (int) $this->get('defaults.warranty_years_steel', 12);
    }

    public function quotationValidityDays(): int
    {
        return (int) $this->get('defaults.quotation_validity_days', 7);
    }
}
