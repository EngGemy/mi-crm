<?php

namespace Tests\Feature;

use App\Filament\Pages\ExhibitionDailyReport as ExhibitionReportPage;
use App\Filament\Resources\PoultryQuotationResource;
use App\Models\ExhibitionAccess;
use App\Models\PoultryQuotation;
use App\Models\User;
use App\Services\Poultry\ExhibitionDailyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExhibitionDailyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sales_rep_cannot_see_another_reps_clients(): void
    {
        $repA = $this->rep('أ');
        $repB = $this->rep('ب');
        $manager = $this->rep('مدير', 'sales_manager');

        $this->quote($repA, 'عميل أ');
        $this->quote($repB, 'عميل ب');

        $this->actingAs($repA);
        $this->assertSame(
            ['عميل أ'],
            PoultryQuotationResource::getEloquentQuery()->pluck('client_name')->all()
        );

        $this->actingAs($manager);
        $this->assertEqualsCanonicalizing(
            ['عميل أ', 'عميل ب'],
            PoultryQuotationResource::getEloquentQuery()->pluck('client_name')->all()
        );
    }

    public function test_daily_report_lists_each_reps_clients_for_that_day_only(): void
    {
        $repA = $this->rep('أ');
        $repB = $this->rep('ب');
        $manager = $this->rep('مدير', 'sales_manager');

        $this->quote($repA, 'عميل أ', 1000);
        $this->quote($repA, 'عميل أ', 500);
        $this->quote($repB, 'عميل ب', 2000);
        $old = $this->quote($repB, 'عميل قديم', 9000);
        $old->forceFill(['created_at' => now()->subDay()])->saveQuietly();

        $report = app(ExhibitionDailyReport::class)->forDate(now(), $manager);

        $this->assertSame(3, $report['quotes_count']);
        $this->assertSame(2, $report['clients_count']);
        $this->assertEquals(3500.0, $report['total']);

        $byName = collect($report['reps'])->keyBy('name');
        $this->assertSame(2, $byName['مندوب أ']['quotes_count']);
        $this->assertSame(1, $byName['مندوب أ']['clients_count']);
        $this->assertSame(1500.0, $byName['مندوب أ']['total']);
        $this->assertSame(['عميل أ', 'عميل أ'], array_column($byName['مندوب أ']['clients'], 'name'));
        $this->assertSame(1, $byName['مندوب ب']['quotes_count']);
        $this->assertSame('عميل ب', $byName['مندوب ب']['clients'][0]['name']);

        $own = app(ExhibitionDailyReport::class)->forDate(now(), $repA);
        $this->assertSame(['مندوب أ'], array_column($own['reps'], 'name'));
        $this->assertSame(2, $own['quotes_count']);
    }

    public function test_view_team_grant_lets_a_rep_see_other_clients(): void
    {
        $repA = $this->rep('أ');
        $repB = $this->rep('ب');
        $this->quote($repA, 'عميل أ');
        $this->quote($repB, 'عميل ب');

        ExhibitionAccess::create([
            'user_id' => $repA->id,
            'report_daily' => true,
            'report_weekly' => true,
            'view_team' => true,
        ]);

        $this->actingAs($repA);
        $this->assertEqualsCanonicalizing(
            ['عميل أ', 'عميل ب'],
            PoultryQuotationResource::getEloquentQuery()->pluck('client_name')->all()
        );
    }

    public function test_weekly_report_covers_saturday_to_friday_for_each_rep(): void
    {
        $rep = $this->rep('أ');
        $manager = $this->rep('مدير', 'sales_manager');

        $inside = $this->quote($rep, 'داخل الأسبوع', 100);
        $inside->forceFill(['created_at' => Carbon::parse('2026-10-03 10:00:00')])->saveQuietly();
        $friday = $this->quote($rep, 'جمعة الأسبوع', 50);
        $friday->forceFill(['created_at' => Carbon::parse('2026-10-09 18:00:00')])->saveQuietly();
        $before = $this->quote($rep, 'قبل الأسبوع', 900);
        $before->forceFill(['created_at' => Carbon::parse('2026-10-02 18:00:00')])->saveQuietly();

        $report = app(ExhibitionDailyReport::class)->forRange(
            Carbon::parse('2026-10-03'),
            Carbon::parse('2026-10-09'),
            $manager,
        );

        $this->assertSame('2026-10-03', $report['from']);
        $this->assertSame('2026-10-09', $report['to']);
        $this->assertSame(2, $report['quotes_count']);
        $this->assertEqualsCanonicalizing(
            ['داخل الأسبوع', 'جمعة الأسبوع'],
            array_column($report['reps'][0]['clients'], 'name')
        );
    }

    public function test_manager_can_turn_daily_and_weekly_reports_off_per_rep(): void
    {
        $rep = $this->rep('أ');
        ExhibitionAccess::create([
            'user_id' => $rep->id,
            'report_daily' => false,
            'report_weekly' => false,
            'view_team' => false,
        ]);

        $this->actingAs($rep);
        $this->assertFalse(ExhibitionReportPage::canAccess());

        $rep->exhibitionAccess->update(['report_weekly' => true]);
        $this->assertTrue(ExhibitionReportPage::canAccess());
    }

    private function rep(string $suffix, string $role = 'sales_rep'): User
    {
        $user = User::create([
            'name' => ($role === 'sales_manager' ? 'مدير ' : 'مندوب ').$suffix,
            'email' => $role.'-'.$suffix.'@example.com',
            'password' => 'secret-secret',
            'is_active' => true,
        ]);

        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    private function quote(User $owner, string $client, float $total = 100): PoultryQuotation
    {
        return PoultryQuotation::withoutEvents(function () use ($owner, $client, $total) {
            return PoultryQuotation::create([
                'quote_number' => 'Q-'.$owner->id.'-'.uniqid(),
                'client_name' => $client,
                'client_phone' => '01000000000',
                'project_type' => 'broiler',
                'length' => 72,
                'width' => 12,
                'height' => 3,
                'tiers' => 3,
                'lines' => 4,
                'total' => $total,
                'status' => 'draft',
                'created_by' => $owner->id,
            ]);
        });
    }
}
