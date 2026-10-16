<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\PoultryQuotation;
use App\Models\User;
use App\Services\Sales\ClientRepsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRepsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_client_lists_every_rep_who_dealt_with_them(): void
    {
        $registrar = $this->user('أحمد');
        $author = $this->user('محمد');
        $editor = $this->user('سارة');

        $this->actingAs($registrar);
        Lead::create([
            'name' => 'مزرعة النيل',
            'phone' => '01011111111',
            'company' => 'النيل',
            'source' => 'walk_in',
            'status' => 'new',
            'created_by' => $registrar->id,
            'assigned_to' => $registrar->id,
        ]);

        $this->actingAs($author);
        $quote = PoultryQuotation::create([
            'client_name' => 'مزرعة النيل',
            'client_phone' => '+20 101 111 1111',
            'client_company' => 'النيل',
            'project_type' => 'broiler',
            'length' => 10,
            'width' => 8,
            'height' => 3,
            'tiers' => 3,
            'lines' => 2,
            'barns_count' => 1,
        ]);

        $this->actingAs($editor);
        $quote->client_notes = 'تعديل السعر';
        $quote->save();

        $rows = app(ClientRepsReportService::class)->rows('1111');

        $this->assertCount(1, $rows);
        $this->assertSame('مزرعة النيل', $rows[0]['name']);

        $byName = collect($rows[0]['reps'])->keyBy('name');
        $this->assertStringContainsString('سجّل العميل', $byName['أحمد']['roles']);
        $this->assertStringContainsString('عرض السعر', $byName['محمد']['roles']);
        $this->assertStringContainsString('تعديل', $byName['سارة']['roles']);

        $onlyEditor = app(ClientRepsReportService::class)->rows(null, $editor->id);
        $this->assertCount(1, $onlyEditor);
        $this->assertSame('مزرعة النيل', $onlyEditor[0]['name']);
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
}
