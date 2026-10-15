<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\PoultryQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoultryQuoteAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_keeps_who_registered_the_client_and_who_priced_it_and_who_edited(): void
    {
        $registrar = $this->user('أحمد');
        $author = $this->user('محمد');
        $editor = $this->user('سارة');

        $this->actingAs($registrar);
        Lead::create([
            'name' => 'مزرعة النيل',
            'phone' => '01011111111',
            'source' => 'walk_in',
            'status' => 'new',
            'created_by' => $registrar->id,
            'assigned_to' => $registrar->id,
        ]);

        $this->actingAs($author);
        $quote = PoultryQuotation::create([
            'client_name' => 'مزرعة النيل',
            'client_phone' => '01011111111',
            'project_type' => 'broiler',
            'length' => 10,
            'width' => 8,
            'height' => 3,
            'tiers' => 3,
            'lines' => 2,
            'barns_count' => 1,
        ]);

        $this->assertSame($registrar->id, $quote->client_added_by);
        $this->assertSame($author->id, $quote->created_by);
        $this->assertSame($registrar->id, Lead::query()->first()->created_by);

        $this->actingAs($editor);
        $quote->client_notes = 'متابعة من حساب تالت';
        $quote->created_by = $editor->id;
        $quote->client_added_by = $editor->id;
        $quote->save();
        $quote->refresh();

        $this->assertSame($author->id, (int) $quote->created_by);
        $this->assertSame($registrar->id, (int) $quote->client_added_by);
        $this->assertSame($editor->id, (int) ($quote->contributors[0]['user_id'] ?? 0));
        $this->assertSame('سارة', $quote->contributors[0]['name'] ?? null);

        $quote->load(['creator', 'clientAdder']);
        $this->assertSame([
            'سجّل العميل: أحمد',
            'عرض السعر: محمد',
            'ساهم بالتعديل: سارة',
        ], $quote->creditLines());

        $visibleToRegistrar = PoultryQuotation::query()
            ->where(function ($query) use ($registrar) {
                $query->where('created_by', $registrar->id)
                    ->orWhere('client_added_by', $registrar->id)
                    ->orWhereJsonContains('contributors', ['user_id' => $registrar->id]);
            })
            ->pluck('id');

        $this->assertTrue($visibleToRegistrar->contains($quote->id));
    }

    public function test_editing_a_lead_cannot_replace_who_registered_it(): void
    {
        $registrar = $this->user('أحمد');
        $other = $this->user('خالد');

        $this->actingAs($registrar);
        $lead = Lead::create([
            'name' => 'عميل المعرض',
            'phone' => '01022222222',
            'source' => 'exhibition',
            'status' => 'new',
            'created_by' => $registrar->id,
        ]);

        $this->actingAs($other);
        $lead->name = 'عميل المعرض بعد التعديل';
        $lead->created_by = $other->id;
        $lead->save();

        $this->assertSame($registrar->id, (int) $lead->fresh()->created_by);
        $this->assertSame('عميل المعرض بعد التعديل', $lead->fresh()->name);
    }

    private function user(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => uniqid('rep').'@example.com',
            'password' => 'secret-secret',
            'is_active' => true,
        ]);
    }
};
