<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class LeadImportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin_import_'.uniqid().'@test.com',
            'password' => Hash::make('password'),
            'phone' => '01'.rand(100000000, 999999999),
            'is_active' => true,
        ]);
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);
    }

    protected function buildXlsxPath(array $dataRows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'leads_').'.xlsx';

        $headers = [
            'الاسم*', 'رقم الهاتف*', 'رقم الواتساب', 'البريد الإلكتروني',
            'الشركة / المزرعة', 'المنصب', 'الدولة', 'المدينة', 'العنوان',
            'نوع المشروع', 'حجم المشروع', 'الميزانية المتوقعة',
            'تاريخ الإغلاق المتوقع (YYYY-MM-DD)',
            'المصدر (facebook/whatsapp/instagram/website/referral/walk_in/phone_call/exhibition/cold_call/other)',
            'تفاصيل المصدر',
            'الحالة (new/contacted/qualified/opportunity/won/lost)',
            'الأولوية (low/medium/high/urgent)',
            'ملاحظات',
        ];

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headers));
        foreach ($dataRows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    /** Uses the page's processImport logic directly via reflection */
    protected function callProcessImport(string $xlsxPath): void
    {
        // Copy file to storage disk so path resolution works
        $storagePath = 'lead-imports/'.basename($xlsxPath);
        $storageDir = storage_path('app/lead-imports');
        if (! is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        copy($xlsxPath, storage_path('app/'.$storagePath));

        $page = new ListLeads;
        $method = new \ReflectionMethod(ListLeads::class, 'processImport');
        $method->setAccessible(true);
        $method->invoke($page, $storagePath);
    }

    /** @test */
    public function import_creates_leads_from_xlsx(): void
    {
        $before = Lead::count();

        $rows = [
            ['محمد علي', '0101111'.rand(100, 999), '0101111001', 'mali@test.com', 'مزرعة النور', '', 'Egypt', 'القاهرة', '', 'تسمين', '50000 طائر', '5000000', '2026-08-01', 'whatsapp', '', 'new', 'high', 'عميل مهتم'],
            ['أحمد سالم', '0102222'.rand(100, 999), '', '', '', '', 'Egypt', 'الإسكندرية', '', '', '', '', '', 'facebook', '', 'contacted', 'medium', ''],
            ['سارة خالد', '0103333'.rand(100, 999), '', 'sara@test.com', '', '', 'Egypt', 'الجيزة', '', '', '', '', '', 'other', '', 'new', 'low', ''],
        ];

        $this->callProcessImport($this->buildXlsxPath($rows));

        $this->assertEquals($before + 3, Lead::count(), 'Should create 3 new leads');
    }

    /** @test */
    public function import_updates_existing_lead_with_same_phone(): void
    {
        $phone = '01099887'.rand(100, 999);
        Lead::create([
            'lead_number' => Lead::generateLeadNumber(),
            'name' => 'اسم قديم',
            'phone' => $phone,
            'status' => 'new',
            'source' => 'facebook',
            'priority' => 'low',
            'created_by' => $this->user->id,
        ]);

        $rows = [['اسم جديد', $phone, '', '', '', '', '', '', '', '', '', '', '', 'whatsapp', '', 'contacted', 'high', '']];
        $this->callProcessImport($this->buildXlsxPath($rows));

        $this->assertEquals(1, Lead::where('phone', $phone)->count(), 'Should not duplicate');
        $this->assertEquals('اسم جديد', Lead::where('phone', $phone)->first()->name);
    }

    /** @test */
    public function importer_normalizes_arabic_enum_labels(): void
    {
        $phone = '0107776'.rand(100, 999);
        $rows = [[
            'اسم تجريبي', $phone, '', '', '', '', '', '', '', '', '', '', '',
            'مكالمة هاتفية',  // Arabic for 'phone_call'
            '',
            'مؤهل',           // Arabic for 'qualified'
            'عالية',          // Arabic for 'high'
            '',
        ]];

        $this->callProcessImport($this->buildXlsxPath($rows));

        $lead = Lead::where('phone', $phone)->first();
        $this->assertNotNull($lead);
        $this->assertEquals('phone_call', $lead->source);
        $this->assertEquals('qualified', $lead->status);
        $this->assertEquals('high', $lead->priority);
    }

    /** @test */
    public function importer_uses_defaults_for_unknown_enum_values(): void
    {
        $phone = '0106665'.rand(100, 999);
        $rows = [[
            'اسم تجريبي', $phone, '', '', '', '', '', '', '', '', '', '', '',
            'قيمة_مجهولة',   // unknown source → 'other'
            '',
            'unknown_status', // unknown status → 'new'
            '???',            // unknown priority → 'medium'
            '',
        ]];

        $this->callProcessImport($this->buildXlsxPath($rows));

        $lead = Lead::where('phone', $phone)->first();
        $this->assertNotNull($lead);
        $this->assertEquals('other', $lead->source);
        $this->assertEquals('new', $lead->status);
        $this->assertEquals('medium', $lead->priority);
    }
}
