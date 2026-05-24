<?php

namespace Tests\Feature;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Models\User;
use App\Services\PoultryHousePricingService;
use Database\Seeders\PoultryPricingSettingsSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PoultryQuotationContractFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PoultryPricingSettingsSeeder::class);
    }

    /** @test */
    public function quotation_preview_image_route_exists_for_main_quotation_model(): void
    {
        $this->assertTrue(route('quotations.image', ['quotation' => 1], false) !== '');
    }

    /** @test */
    public function sales_quotation_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('sales.quotations.create'));
        $this->assertTrue(Route::has('sales.quotations.calculate'));
        $this->assertTrue(Route::has('sales.quotations.image'));
        $this->assertTrue(Route::has('api.poultry.calculate'));
    }

    /** @test */
    public function api_calculate_returns_broiler_golden_case_json(): void
    {
        $user = User::create([
            'name' => 'API Test',
            'email' => 'api'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson(route('api.poultry.calculate'), [
                'project_type' => 'broiler',
                'length' => 108,
                'width' => 15,
                'height' => 3.5,
                'service_length' => 10,
                'tiers' => 4,
                'lines' => 5,
                'birds_per_nest' => 16,
                'bird_weight_kg' => 2.1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('bird_count', 62720);
        $response->assertJsonPath('nests_per_line', 784);
    }

    /** @test */
    public function contract_totals_match_quotation_computation_snapshot(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $service = new PoultryHousePricingService;
        $input = [
            'project_type' => PoultryProjectType::Broiler->value,
            'pricing_scope' => PoultryPricingScope::FullProject->value,
            'hall_length' => 108,
            'hall_width' => 15,
            'hall_height' => 3.5,
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 5,
            'birds_per_nest' => 16,
            'bird_weight_kg' => 2.1,
            'side_fans_count' => 6,
            'heaters_count' => 2,
        ];

        $result = $service->compute($input);

        $quotation = PoultryQuotation::create([
            'client_name' => 'Test Client',
            'length' => 108,
            'width' => 15,
            'height' => 3.5,
            'project_type' => 'broiler',
            'pricing_scope' => 'full_project',
            'service_length' => 10,
            'tiers' => 4,
            'lines' => 5,
            'birds_per_nest' => 16,
            'bird_weight_kg' => 2.1,
            'side_fans_count' => 6,
            'heaters_count' => 2,
            'pricing_snapshot' => $result,
            'subtotal' => $result['subtotal'],
            'total' => $result['subtotal'],
        ]);

        $this->assertEquals(62720, $quotation->fresh()->bird_count);
        $this->assertEquals($result['subtotal'], (float) $quotation->subtotal);
    }
}
