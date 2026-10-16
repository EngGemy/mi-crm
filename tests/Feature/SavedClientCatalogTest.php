<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\PoultryQuotation;
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

        $ahmed = Lead::create([
            'lead_number' => 'LEAD-2',
            'name' => 'أحمد معرض',
            'phone' => '01022222222',
            'whatsapp' => '01011111111',
            'source' => 'exhibition',
        ]);

        $sara = Lead::create([
            'lead_number' => 'LEAD-3',
            'name' => 'سارة',
            'phone' => '01033333333',
            'source' => 'facebook',
        ]);

        $options = SavedClientCatalog::options();

        $this->assertEqualsCanonicalizing(
            ['customer:'.$customer->id, 'lead:'.$ahmed->id, 'lead:'.$sara->id],
            array_keys($options)
        );
        $this->assertStringContainsString('محمد جمال', $options['customer:'.$customer->id]);
        $this->assertStringContainsString('أحمد معرض', $options['lead:'.$ahmed->id]);
        $this->assertStringContainsString('سارة', $options['lead:'.$sara->id]);
    }

    public function test_search_matches_arabic_spelling_and_phone_formats(): void
    {
        $lead = Lead::create([
            'lead_number' => 'LEAD-8',
            'name' => 'أحمد المعرض',
            'phone' => '+20 102 222 2222',
            'company' => 'مزرعة الأمل',
            'source' => 'exhibition',
        ]);

        $byName = SavedClientCatalog::search('احمد المعرض');
        $byLocalPhone = SavedClientCatalog::search('01022222222');
        $byShort = SavedClientCatalog::search('022222222');

        $this->assertArrayHasKey('lead:'.$lead->id, $byName);
        $this->assertArrayHasKey('lead:'.$lead->id, $byLocalPhone);
        $this->assertArrayHasKey('lead:'.$lead->id, $byShort);
        $this->assertStringContainsString('محتمل', $byName['lead:'.$lead->id]);
    }

    public function test_saving_a_quote_adds_the_client_to_potential_clients(): void
    {
        PoultryQuotation::create([
            'client_name' => 'عميل الحاسبة',
            'client_phone' => '01055555555',
            'project_type' => 'broiler',
            'length' => 10,
            'width' => 8,
            'height' => 3,
            'tiers' => 3,
            'lines' => 2,
            'barns_count' => 1,
        ]);

        $lead = Lead::query()->where('name', 'عميل الحاسبة')->first();

        $this->assertNotNull($lead);
        $this->assertSame('1055555555', $lead->phone_key);
        $this->assertSame('حاسبة الأسعار', $lead->source_details);
        $this->assertArrayHasKey('lead:'.$lead->id, SavedClientCatalog::search('+201055555555'));
    }

    public function test_deleted_quote_stays_in_trash_until_the_retention_period_ends(): void
    {
        $fresh = $this->quote('عميل جديد', '01066666666');
        $expired = $this->quote('عميل قديم', '01077777777');

        $fresh->delete();
        $expired->delete();
        $expired->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

        $this->assertNotNull(PoultryQuotation::withTrashed()->find($fresh->id));
        $this->artisan('trash:purge')->assertSuccessful();

        $this->assertNotNull(PoultryQuotation::withTrashed()->find($fresh->id));
        $this->assertNull(PoultryQuotation::withTrashed()->find($expired->id));
    }

    private function quote(string $name, string $phone): PoultryQuotation
    {
        return PoultryQuotation::create([
            'client_name' => $name,
            'client_phone' => $phone,
            'project_type' => 'broiler',
            'length' => 10,
            'width' => 8,
            'height' => 3,
            'tiers' => 3,
            'lines' => 2,
            'barns_count' => 1,
        ]);
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
