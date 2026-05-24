<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationType;
use App\Models\Setting;
use App\Models\User;
use App\Services\PoultryHousePricingService;
use App\Services\QuotationGenerator;
use App\Support\PoultrySectionLabels;
use Database\Seeders\PoultryPricingSettingsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PoultryAutoPricingFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PoultryPricingSettingsSeeder::class);
        (new \Database\Seeders\QuotationSeeder)->seedQuotationSections();
    }

    protected function createSalesUser(): User
    {
        $email = 'sales'.uniqid().'@test.com';
        $user = User::create([
            'name' => 'Sales Rep',
            'email' => $email,
            'password' => Hash::make('password'),
            'phone' => '01234567890',
            'is_active' => true,
        ]);
        $user->assignRole('sales_rep');

        return $user;
    }

    protected function createQuotation(): Quotation
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '01234567890',
            'address' => 'Cairo',
        ]);

        $type = QuotationType::firstOrCreate(
            ['code' => 'FULL_PROJECT'],
            ['name' => 'FULL_PROJECT', 'is_active' => true]
        );

        return Quotation::create([
            'quotation_number' => 'QT-2026-T'.uniqid(),
            'customer_id' => $customer->id,
            'quotation_type_id' => $type->id,
            'quotation_date' => now(),
            'valid_until' => now()->addDays(7),
            'validity_period_days' => 7,
            'project_name' => 'Test Project',
            'status' => 'draft',
            'currency' => 'EGP',
            'language' => 'both',
            'created_by' => $this->createSalesUser()->id,
        ]);
    }

    /** @test */
    public function auto_pricing_applies_to_quotation_and_creates_items(): void
    {
        $quotation = $this->createQuotation();
        $service = app(PoultryHousePricingService::class);

        $result = $service->applyToQuotation($quotation, [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 81,
            'hall_width' => 12,
            'hall_height' => 3.5,
            'service_length' => 6,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 16,
            'bird_weight_kg' => 2.1,
            'side_fans_count' => 8,
            'heaters_count' => 4,
        ]);

        $quotation->refresh();

        $this->assertEquals(38400, $quotation->bird_capacity);
        $this->assertEquals(17, $quotation->back_fans_count);
        $this->assertEquals(94, (float) $quotation->cooling_units);
        $this->assertEquals(39, $quotation->windows_count);
        $this->assertGreaterThanOrEqual(10, $quotation->items()->count());
        $this->assertNotNull($quotation->pricing_snapshot);
        $this->assertArrayHasKey('parameters', $quotation->pricing_snapshot);
        $this->assertArrayHasKey('items', $quotation->pricing_snapshot);
        $this->assertGreaterThan(0, (float) $quotation->subtotal);
        $this->assertGreaterThan(0, (float) $quotation->total_amount);
    }

    /** @test */
    public function snapshot_preserves_old_prices_after_settings_change(): void
    {
        $quotation = $this->createQuotation();
        $service = app(PoultryHousePricingService::class);

        $service->applyToQuotation($quotation, [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 81,
            'hall_width' => 12,
            'hall_height' => 3.5,
            'service_length' => 6,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 16,
            'bird_weight_kg' => 2.1,
            'side_fans_count' => 8,
            'heaters_count' => 4,
        ]);

        $originalSubtotal = (float) $quotation->fresh()->subtotal;

        // Change a setting
        Setting::where('key', 'poultry_pricing.price_per_bird')->update(['value' => '999']);

        // Recompute from snapshot should use old params
        $recomputed = $service->recomputeFromSnapshot($quotation->fresh());
        $this->assertEquals($originalSubtotal, $recomputed['subtotal']);
    }

    /** @test */
    public function quotation_has_image_route_registered(): void
    {
        $quotation = $this->createQuotation();
        $this->assertNotNull(route('quotations.image', $quotation));
        $this->assertNotNull(route('quotations.image.download', $quotation));
    }

    /** @test */
    public function battery_item_hides_unit_price_and_uses_flat_label(): void
    {
        $quotation = $this->createQuotation();
        $service = app(PoultryHousePricingService::class);

        $result = $service->applyToQuotation($quotation, [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 81,
            'hall_width' => 12,
            'hall_height' => 3.5,
            'service_length' => 6,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 16,
            'bird_weight_kg' => 2.1,
            'side_fans_count' => 8,
            'heaters_count' => 4,
        ]);

        $batteryItem = collect($result['items'])->firstWhere('key', 'battery');
        $this->assertNotNull($batteryItem);
        $this->assertEquals('بطاريات العنبر', $batteryItem['desc_ar']);
        $this->assertTrue($batteryItem['hide_unit_details'] ?? false);
        $this->assertEquals('lot', $batteryItem['unit']);
        $this->assertEquals(1, $batteryItem['qty']);

        // Total should be bird_count * price_per_bird (flat)
        $expectedTotal = round(38400 * 95, 2);
        $this->assertEquals($expectedTotal, $batteryItem['total_price']);
    }

    /** @test */
    public function display_groups_merge_sections_into_three_tables(): void
    {
        $groups = PoultrySectionLabels::displayGroupsAr();
        $this->assertEquals('الإنشاءات', $groups['civil']);
        $this->assertEquals('بطاريات العنبر', $groups['cages']);
        $this->assertEquals('المشتملات', $groups['ventilation']);
        $this->assertEquals('المشتملات', $groups['cooling']);
        $this->assertEquals('المشتملات', $groups['technical']);
        $this->assertEquals('المشتملات', $groups['electrical']);

        $quotation = $this->createQuotation();
        $service = app(PoultryHousePricingService::class);
        $result = $service->applyToQuotation($quotation, [
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'hall_length' => 81,
            'hall_width' => 12,
            'hall_height' => 3.5,
            'service_length' => 6,
            'tiers' => 4,
            'lines' => 4,
            'birds_per_nest' => 16,
            'bird_weight_kg' => 2.1,
            'side_fans_count' => 8,
            'heaters_count' => 4,
        ]);

        // Generator should group into 3 display groups for poultry quotations
        $generator = app(QuotationGenerator::class);
        $grouped = $generator->groupItemsBySection($quotation->fresh());
        $this->assertArrayHasKey('الإنشاءات', $grouped);
        $this->assertArrayHasKey('بطاريات العنبر', $grouped);
        $this->assertArrayHasKey('المشتملات', $grouped);

        // Group subtotals should sum correctly
        $groupSubtotals = [];
        foreach ($grouped as $groupName => $items) {
            $groupSubtotals[$groupName] = collect($items)->sum(fn ($item) => (float) $item->total_price);
        }
        $this->assertGreaterThan(0, $groupSubtotals['الإنشاءات']);
        $this->assertGreaterThan(0, $groupSubtotals['بطاريات العنبر']);
        $this->assertGreaterThan(0, $groupSubtotals['المشتملات']);

        // Control and electricity are separate items inside accessories
        $accessoryItems = collect($grouped['المشتملات'] ?? []);
        $controlItem = $accessoryItems->first(fn ($i) => str_contains($i->description_ar, 'مونيتر'));
        $electricityItem = $accessoryItems->first(fn ($i) => str_contains($i->description_ar, 'الكهرباء والإنارة'));
        $this->assertNotNull($controlItem);
        $this->assertNotNull($electricityItem);
    }
}
