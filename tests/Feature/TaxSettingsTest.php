<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Pricing\DTOs\QuotationInput;
use App\Services\Pricing\PricingCalculator;
use App\Services\SettingsService;
use Database\Seeders\PoultryPricingSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxAndFinanceSettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaxSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PoultryPricingSettingsSeeder::class);
        $this->seed(TaxAndFinanceSettingsSeeder::class);
    }

    /** @test */
    public function changing_vat_rate_egypt_changes_pricing_output(): void
    {
        // Set Egypt VAT to 14%
        Setting::updateOrCreate(['key' => 'tax.vat_rate_egypt'], ['value' => '14']);
        app(SettingsService::class)->clearCache();

        $input = new QuotationInput(
            length: 120,
            width: 14,
            height: 2.8,
            tiers: 4,
            lines: 8,
            vatRegion: 'egypt',
        );

        $result1 = (new PricingCalculator)->calculate($input);
        $vat1 = (float) $result1->vatAmount;

        // Change Egypt VAT to 20%
        Setting::where('key', 'tax.vat_rate_egypt')->update(['value' => '20']);
        app(SettingsService::class)->clearCache();

        $result2 = (new PricingCalculator)->calculate($input);
        $vat2 = (float) $result2->vatAmount;

        $this->assertGreaterThan($vat1, $vat2, 'VAT amount should increase when rate rises from 14% to 20%');

        // Verify ratio — vat2/subtotal ≈ 20%, vat1/subtotal ≈ 14%
        $subtotal = (float) $result1->subtotal;
        $this->assertEqualsWithDelta($subtotal * 0.14, $vat1, 1.0);
        $this->assertEqualsWithDelta($subtotal * 0.20, $vat2, 1.0);
    }

    /** @test */
    public function ksa_vat_uses_its_own_rate(): void
    {
        Setting::updateOrCreate(['key' => 'tax.vat_rate_ksa'], ['value' => '15']);
        app(SettingsService::class)->clearCache();

        $input = new QuotationInput(
            length: 100,
            width: 12,
            height: 2.6,
            tiers: 4,
            lines: 6,
            vatRegion: 'ksa',
        );

        $result = (new PricingCalculator)->calculate($input);
        $subtotal = (float) $result->subtotal;
        $vat = (float) $result->vatAmount;

        $this->assertEqualsWithDelta($subtotal * 0.15, $vat, 1.0);
    }

    /** @test */
    public function no_vat_region_yields_zero_vat(): void
    {
        $input = new QuotationInput(
            length: 100,
            width: 12,
            height: 2.6,
            tiers: 4,
            lines: 6,
            vatRegion: 'none',
        );

        $result = (new PricingCalculator)->calculate($input);

        $this->assertEquals('0.00', $result->vatAmount);
    }

    /** @test */
    public function tax_and_finance_seeder_is_idempotent(): void
    {
        $countBefore = Setting::whereIn('key', [
            'tax.vat_rate_egypt',
            'tax.vat_rate_ksa',
            'tax.default_vat_region',
            'finance.default_discount_percentage',
            'finance.default_exchange_rate',
            'finance.payment_schedule',
        ])->count();

        $this->seed(TaxAndFinanceSettingsSeeder::class);

        $countAfter = Setting::whereIn('key', [
            'tax.vat_rate_egypt',
            'tax.vat_rate_ksa',
            'tax.default_vat_region',
            'finance.default_discount_percentage',
            'finance.default_exchange_rate',
            'finance.payment_schedule',
        ])->count();

        $this->assertEquals($countBefore, $countAfter, 'Seeder should be idempotent (updateOrCreate)');
    }
}
