<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Services\Poultry\SavedClientCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedClientCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_person_saved_as_customer_and_lead_is_listed_once(): void
    {
        $customer = Customer::create([
            'name' => 'محمد جمال',
            'phone' => '01011111111',
            'address' => 'القاهرة',
        ]);

        Lead::create([
            'lead_number' => 'LEAD-1',
            'name' => 'محمد جمال',
            'phone' => '+20 101 111 1111',
            'source' => 'exhibition',
            'customer_id' => $customer->id,
        ]);

        Lead::create([
            'lead_number' => 'LEAD-2',
            'name' => 'أحمد معرض',
            'phone' => '01022222222',
            'whatsapp' => '01011111111',
            'source' => 'exhibition',
        ]);

        Lead::create([
            'lead_number' => 'LEAD-3',
            'name' => 'سارة',
            'phone' => '01033333333',
            'source' => 'facebook',
        ]);

        $options = SavedClientCatalog::options();

        $this->assertSame(
            ['customer:'.$customer->id, 'lead:3'],
            array_keys($options)
        );
        $this->assertStringContainsString('محمد جمال', $options['customer:'.$customer->id]);
        $this->assertStringContainsString('سارة', $options['lead:3']);
    }

    public function test_choosing_a_lead_fills_the_quote_and_does_not_invent_a_customer(): void
    {
        $lead = Lead::create([
            'lead_number' => 'LEAD-9',
            'name' => 'خالد',
            'phone' => '01099999999',
            'company' => 'مزرعة خالد',
            'city' => 'كفر الشيخ',
            'source' => 'exhibition',
        ]);

        $fields = SavedClientCatalog::fieldsFor('lead:'.$lead->id);

        $this->assertSame('خالد', $fields['client_name']);
        $this->assertSame('01099999999', $fields['client_phone']);
        $this->assertSame('مزرعة خالد', $fields['client_company']);
        $this->assertNull($fields['customer_id']);
    }
}
