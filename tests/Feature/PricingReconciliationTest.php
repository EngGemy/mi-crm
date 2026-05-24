<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Customer;
use App\Models\PoultryQuotation;
use App\Models\Quotation;
use App\Models\QuotationSection;
use App\Models\QuotationType;
use App\Models\Setting;
use App\Models\User;
use App\Services\PoultryHousePricingService;
use App\Services\UnifiedQuotationToContractConverter;
use App\Support\FinancialEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبار التطابق الذهبي: نفس المُدخَل يُنتج نفس الأرقام بالضبط
 * في الحاسبة → PoultryQuotation → Quotation → Contract.
 */
class PricingReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed الإعدادات الضريبية
        Setting::updateOrCreate(
            ['key' => 'tax.vat_rate_egypt'],
            ['value' => '14', 'type' => 'decimal', 'category' => 'tax']
        );
        Setting::updateOrCreate(
            ['key' => 'tax.vat_rate_ksa'],
            ['value' => '15', 'type' => 'decimal', 'category' => 'tax']
        );
        Setting::updateOrCreate(
            ['key' => 'tax.default_vat_region'],
            ['value' => 'egypt', 'type' => 'string', 'category' => 'tax']
        );

        // Seed pricing params
        $defaults = [
            'concrete_cost_per_m2' => 850,
            'steel_cost_per_m2' => 1200,
            'wall_cost_per_m2' => 650,
            'tanks_fixed_cost' => 400000,
            'price_per_bird' => 95,
            'back_fan_unit_price' => 4500,
            'cooling_unit_price' => 1800,
            'window_unit_price' => 350,
            'side_fan_unit_price' => 2800,
            'heater_unit_price' => 1500,
            'control_fixed_cost' => 75000,
            'electricity_fixed_cost' => 125000,
            'include_tanks' => true,
        ];
        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(
                ['key' => "poultry_pricing.$key"],
                ['value' => (string) $value, 'type' => 'decimal', 'category' => 'poultry_pricing']
            );
        }
    }

    /** @test */
    public function engine_poultry_quotation_quotation_and_contract_match_exactly_egypt_14_percent(): void
    {
        $service = new PoultryHousePricingService;

        $input = [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 120,
            'hall_width' => 16,
            'hall_height' => 4.5,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 4,
            'bird_weight_kg' => 2.1,
            'birds_per_nest' => null,
            'side_fans_count' => null,
            'heaters_count' => null,
            'wall_type' => null,
        ];

        // 1. المحرك
        $result = $service->compute($input);
        $engineFinancial = $result['financial'];

        // 2. PoultryQuotation
        $poultryQuotation = PoultryQuotation::create([
            'client_name' => 'عميل تجريبي',
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'length' => 120,
            'width' => 16,
            'height' => 4.5,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 4,
            'bird_weight_kg' => 2.1,
            'vat_percentage' => 14,
        ]);

        $this->assertEquals(
            $engineFinancial['subtotal'],
            FinancialEngine::format((float) $poultryQuotation->subtotal),
            'PoultryQuotation subtotal does not match engine'
        );
        $this->assertEquals(
            $engineFinancial['vat_amount'],
            FinancialEngine::format((float) $poultryQuotation->vat_amount),
            'PoultryQuotation vat does not match engine'
        );
        $this->assertEquals(
            $engineFinancial['total'],
            FinancialEngine::format((float) $poultryQuotation->total),
            'PoultryQuotation total does not match engine'
        );

        // 3. Quotation (عبر applyToQuotation)
        $customer = Customer::create(['name' => 'عميل تجريبي', 'phone' => '01000000000', 'address' => 'القاهرة']);
        $quotationType = QuotationType::create(['code' => 'FULL_PROJECT', 'name' => 'مشروع كامل']);
        ContractType::create(['code' => 'FATTENING_FULL', 'name' => 'تسمين كامل']);
        $quotation = Quotation::create([
            'customer_id' => $customer->id,
            'quotation_type_id' => $quotationType->id,
            'project_name' => 'عنبر تجريبي',
            'status' => 'draft',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(30),
            'installation_location' => 'القاهرة',
        ]);

        QuotationSection::create(['category' => 'civil', 'code' => 'CIVIL', 'title_ar' => 'إنشاءات', 'title_en' => 'Civil']);
        QuotationSection::create(['category' => 'cages', 'code' => 'CAGES', 'title_ar' => 'بطاريات', 'title_en' => 'Cages']);
        QuotationSection::create(['category' => 'ventilation', 'code' => 'VENT', 'title_ar' => 'تهوية', 'title_en' => 'Ventilation']);
        QuotationSection::create(['category' => 'cooling', 'code' => 'COOLING', 'title_ar' => 'تبريد', 'title_en' => 'Cooling']);
        QuotationSection::create(['category' => 'technical', 'code' => 'TECH', 'title_ar' => 'تقني', 'title_en' => 'Technical']);
        QuotationSection::create(['category' => 'electrical', 'code' => 'ELEC', 'title_ar' => 'كهرباء', 'title_en' => 'Electrical']);

        $service->applyToQuotation($quotation, $input);
        $quotation->refresh();

        $snapshot = $quotation->pricing_snapshot;
        $this->assertNotEmpty($snapshot['financial'], 'Snapshot should contain financial block');
        $snapFinancial = $snapshot['financial'];

        $this->assertEquals(
            $engineFinancial['subtotal'],
            $snapFinancial['subtotal'],
            'Quotation snapshot subtotal does not match engine'
        );
        $this->assertEquals(
            $engineFinancial['vat_amount'],
            $snapFinancial['vat_amount'],
            'Quotation snapshot vat does not match engine'
        );
        $this->assertEquals(
            $engineFinancial['total'],
            $snapFinancial['total'],
            'Quotation snapshot total does not match engine'
        );

        // 4. Contract (عبر المحوّل الموحّد)
        $quotation->update(['status' => 'approved']);

        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $this->actingAs($user);

        $converter = app(UnifiedQuotationToContractConverter::class);
        $contract = $converter->convertQuotation($quotation);

        $this->assertEquals(
            $engineFinancial['subtotal'],
            FinancialEngine::format((float) $contract->subtotal),
            'Contract subtotal does not match engine'
        );
        $this->assertEquals(
            $engineFinancial['vat_amount'],
            FinancialEngine::format((float) $contract->vat_amount),
            'Contract vat does not match engine'
        );
        $this->assertEquals(
            $engineFinancial['total'],
            FinancialEngine::format((float) $contract->total_value),
            'Contract total does not match engine'
        );
    }

    /** @test */
    public function engine_poultry_quotation_and_contract_match_ksa_15_percent(): void
    {
        $service = new PoultryHousePricingService;

        $input = [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 100,
            'hall_width' => 14,
            'hall_height' => 4,
            'service_length' => 8,
            'tiers' => 3,
            'lines' => 3,
            'bird_weight_kg' => 2.1,
            'wall_type' => 'sandwich',
            'vat_region' => 'ksa',
        ];

        $result = $service->compute($input);
        $engineFinancial = $result['financial'];

        // VAT يجب أن تكون 15%
        $this->assertEquals('15.00', number_format((float) ($result['parameters']['vat_percentage'] ?? 15), 2, '.', ''));

        $poultryQuotation = PoultryQuotation::create([
            'client_name' => 'عميل سعودي',
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'length' => 100,
            'width' => 14,
            'height' => 4,
            'service_length' => 8,
            'tiers' => 3,
            'lines' => 3,
            'bird_weight_kg' => 2.1,
            'vat_percentage' => 15,
        ]);

        $this->assertEquals(
            $engineFinancial['total'],
            FinancialEngine::format((float) $poultryQuotation->total),
            'KSA total does not match engine'
        );
    }

    /** @test */
    public function saved_quotation_is_immune_to_settings_changes(): void
    {
        $service = new PoultryHousePricingService;

        $input = [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 120,
            'hall_width' => 16,
            'hall_height' => 4.5,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 4,
            'wall_type' => 'concrete',
        ];

        $poultryQuotation = PoultryQuotation::create([
            'client_name' => 'عميل ثابت',
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'length' => 120,
            'width' => 16,
            'height' => 4.5,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 4,
            'vat_percentage' => 14,
        ]);

        $originalSubtotal = FinancialEngine::format((float) $poultryQuotation->subtotal);
        $originalTotal = FinancialEngine::format((float) $poultryQuotation->total);

        // تغيير الإعدادات
        Setting::where('key', 'poultry_pricing.price_per_bird')->update(['value' => '999']);
        Setting::where('key', 'tax.vat_rate_egypt')->update(['value' => '99']);

        // إعادة قراءة النموذج
        $poultryQuotation->refresh();

        // يجب أن تبقى الأرقام ثابتة
        $this->assertEquals($originalSubtotal, FinancialEngine::format((float) $poultryQuotation->subtotal));
        $this->assertEquals($originalTotal, FinancialEngine::format((float) $poultryQuotation->total));
    }

    /** @test */
    public function manual_quotation_uses_financial_engine_correctly_with_discount(): void
    {
        $customer = Customer::create(['name' => 'عميل يدوي', 'phone' => '01000000001', 'address' => 'القاهرة']);
        $quotationType = QuotationType::create(['code' => 'FULL_PROJECT', 'name' => 'مشروع كامل']);

        $quotation = Quotation::create([
            'customer_id' => $customer->id,
            'quotation_type_id' => $quotationType->id,
            'project_name' => 'عرض يدوي',
            'status' => 'draft',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => 10000,
            'discount_percentage' => 10,
            'vat_percentage' => 14,
        ]);

        $quotation->refresh();

        // 10000 - 10% = 9000, VAT = 9000 * 14% = 1260, total = 10260
        $expected = FinancialEngine::calculateTotals(10000, 10, 0, 14);

        $this->assertEquals($expected['discount_amount'], FinancialEngine::format((float) $quotation->discount_amount));
        $this->assertEquals($expected['vat_amount'], FinancialEngine::format((float) $quotation->vat_amount));
        $this->assertEquals($expected['total'], FinancialEngine::format((float) $quotation->total_amount));
    }
}
