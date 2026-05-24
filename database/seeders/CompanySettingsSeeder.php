<?php

namespace Database\Seeders;

use App\Models\CompanyBankAccount;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedBankAccounts();
    }

    protected function seedSettings(): void
    {
        $settings = [
            // ═══════════ Category: company ═══════════
            [
                'key' => 'company.name_ar',
                'value' => 'إم آي للصناعات المعدنية',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'اسم الشركة بالعربية',
                'label_en' => 'Company Name (Arabic)',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'company.name_en',
                'value' => 'MI Metal Industries',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'اسم الشركة بالإنجليزية',
                'label_en' => 'Company Name (English)',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'company.tagline_ar',
                'value' => 'بطاريات الدواجن الأوتوماتيك',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'الشعار التسويقي بالعربية',
                'sort_order' => 3,
            ],
            [
                'key' => 'company.tagline_en',
                'value' => 'Automatic Poultry Cages',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'الشعار التسويقي بالإنجليزية',
                'sort_order' => 4,
            ],
            [
                'key' => 'company.owner_name_ar',
                'value' => 'محمد مأمون مصطفى المغربي',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'اسم المالك / المدير',
                'sort_order' => 5,
            ],
            [
                'key' => 'company.owner_title_ar',
                'value' => 'رئيس مجلس الإدارة',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'صفة المالك',
                'sort_order' => 6,
            ],
            [
                'key' => 'company.legal_form_ar',
                'value' => 'شركة',
                'type' => 'string',
                'category' => 'company',
                'label_ar' => 'الشكل القانوني',
                'options' => ['شركة', 'مؤسسة', 'مصنع', 'فرد'],
                'sort_order' => 7,
            ],
            [
                'key' => 'company.established_year',
                'value' => '2018',
                'type' => 'integer',
                'category' => 'company',
                'label_ar' => 'سنة التأسيس',
                'sort_order' => 8,
            ],
            [
                'key' => 'company.about_ar',
                'value' => 'شركة متخصصة في تصميم وتصنيع وتوريد أنظمة بطاريات الدواجن الأوتوماتيكية ومستلزمات مزارع الدواجن الحديثة. تقع الشركة في مدينة دمياط — عاصمة الصناعة المعدنية في مصر — وتتميز بخبرة تمتد لسنوات في مجال تصنيع الهياكل المعدنية والأقفاص الأوتوماتيكية.',
                'type' => 'text',
                'category' => 'company',
                'label_ar' => 'نبذة عن الشركة (تظهر في PDF)',
                'sort_order' => 9,
            ],
            [
                'key' => 'company.about_en',
                'value' => 'MI-Metal Industries CO, located in Damietta, Egypt. A professional manufacturer for poultry farms, specializing in automatic poultry cage systems and modern farm equipment.',
                'type' => 'text',
                'category' => 'company',
                'label_ar' => 'نبذة بالإنجليزية',
                'sort_order' => 10,
            ],

            // ═══════════ Category: contact ═══════════
            [
                'key' => 'contact.address_ar',
                'value' => 'طريق رأس البر القديم - السنانية - دمياط',
                'type' => 'text',
                'category' => 'contact',
                'label_ar' => 'العنوان بالعربية',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'contact.address_en',
                'value' => 'Egypt, Damietta, Old Damietta, Sinaniya, Old Ras El-Bar Road',
                'type' => 'text',
                'category' => 'contact',
                'label_ar' => 'العنوان بالإنجليزية',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'contact.city',
                'value' => 'Damietta',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'المدينة',
                'sort_order' => 3,
            ],
            [
                'key' => 'contact.country',
                'value' => 'Egypt',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'الدولة',
                'sort_order' => 4,
            ],
            [
                'key' => 'contact.phones',
                'value' => json_encode(['+201026253004', '+201011004114', '+20572363679']),
                'type' => 'array',
                'category' => 'contact',
                'label_ar' => 'أرقام التليفون',
                'description' => 'أضف رقم في كل سطر',
                'sort_order' => 5,
            ],
            [
                'key' => 'contact.whatsapp',
                'value' => '+201026253004',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'رقم الواتساب',
                'sort_order' => 6,
            ],
            [
                'key' => 'contact.email',
                'value' => 'mi.cnc.factory@gmail.com',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'البريد الإلكتروني الرئيسي',
                'validation_rules' => ['required', 'email'],
                'is_required' => true,
                'sort_order' => 7,
            ],
            [
                'key' => 'contact.email_secondary',
                'value' => '',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'بريد إلكتروني ثانوي',
                'validation_rules' => ['nullable', 'email'],
                'sort_order' => 8,
            ],
            [
                'key' => 'contact.website',
                'value' => 'www.mi-cnc.com',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'الموقع الإلكتروني',
                'sort_order' => 9,
            ],
            [
                'key' => 'contact.facebook',
                'value' => '',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'رابط فيسبوك',
                'sort_order' => 10,
            ],
            [
                'key' => 'contact.linkedin',
                'value' => '',
                'type' => 'string',
                'category' => 'contact',
                'label_ar' => 'رابط لينكد إن',
                'sort_order' => 11,
            ],

            // ═══════════ Category: legal ═══════════
            [
                'key' => 'legal.tax_number',
                'value' => '194/577/443',
                'type' => 'string',
                'category' => 'legal',
                'label_ar' => 'الرقم الضريبي',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'legal.commercial_register',
                'value' => '97483',
                'type' => 'string',
                'category' => 'legal',
                'label_ar' => 'السجل التجاري',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'legal.license_number',
                'value' => '',
                'type' => 'string',
                'category' => 'legal',
                'label_ar' => 'رقم الترخيص الصناعي',
                'sort_order' => 3,
            ],
            [
                'key' => 'legal.default_vat_percentage',
                'value' => '14',
                'type' => 'decimal',
                'category' => 'legal',
                'label_ar' => 'نسبة ضريبة القيمة المضافة الافتراضية %',
                'description' => 'مصر 14%، السعودية 15%',
                'sort_order' => 4,
            ],
            [
                'key' => 'legal.jurisdiction_ar',
                'value' => 'المحاكم المصرية',
                'type' => 'string',
                'category' => 'legal',
                'label_ar' => 'الاختصاص القضائي',
                'sort_order' => 5,
            ],
            [
                'key' => 'legal.governing_law_ar',
                'value' => 'جمهورية مصر العربية',
                'type' => 'string',
                'category' => 'legal',
                'label_ar' => 'القانون الحاكم',
                'sort_order' => 6,
            ],

            // ═══════════ Category: branding ═══════════
            [
                'key' => 'branding.primary_color',
                'value' => '#C00000',
                'type' => 'color',
                'category' => 'branding',
                'label_ar' => 'اللون الأساسي',
                'sort_order' => 1,
            ],
            [
                'key' => 'branding.secondary_color',
                'value' => '#2B2B2B',
                'type' => 'color',
                'category' => 'branding',
                'label_ar' => 'اللون الثانوي',
                'sort_order' => 2,
            ],
            [
                'key' => 'branding.accent_color',
                'value' => '#FFEBEB',
                'type' => 'color',
                'category' => 'branding',
                'label_ar' => 'لون التمييز',
                'sort_order' => 3,
            ],
            [
                'key' => 'branding.logo_main',
                'value' => 'images/brand/logo.png',
                'type' => 'image',
                'category' => 'branding',
                'label_ar' => 'الشعار الرئيسي',
                'description' => 'يظهر في PDFs والـ Admin Panel',
                'sort_order' => 4,
            ],
            [
                'key' => 'branding.logo_header',
                'value' => 'images/brand/logo-header.png',
                'type' => 'image',
                'category' => 'branding',
                'label_ar' => 'شعار الهيدر (PDF)',
                'sort_order' => 5,
            ],
            [
                'key' => 'branding.logo_footer',
                'value' => 'images/brand/logo-footer.png',
                'type' => 'image',
                'category' => 'branding',
                'label_ar' => 'شعار الفوتر (PDF)',
                'sort_order' => 6,
            ],
            [
                'key' => 'branding.qr_code',
                'value' => 'images/brand/qr-code.png',
                'type' => 'image',
                'category' => 'branding',
                'label_ar' => 'QR Code (يظهر في الفوتر)',
                'sort_order' => 7,
            ],
            [
                'key' => 'branding.watermark',
                'value' => 'images/brand/watermark.png',
                'type' => 'image',
                'category' => 'branding',
                'label_ar' => 'العلامة المائية',
                'sort_order' => 8,
            ],
            [
                'key' => 'branding.favicon',
                'value' => 'favicon.ico',
                'type' => 'image',
                'category' => 'branding',
                'label_ar' => 'أيقونة المتصفح',
                'sort_order' => 9,
            ],

            // ═══════════ Category: defaults ═══════════
            [
                'key' => 'defaults.currency',
                'value' => 'EGP',
                'type' => 'string',
                'category' => 'defaults',
                'label_ar' => 'العملة الافتراضية',
                'options' => ['EGP', 'USD', 'SAR', 'AED'],
                'sort_order' => 1,
            ],
            [
                'key' => 'defaults.language',
                'value' => 'both',
                'type' => 'string',
                'category' => 'defaults',
                'label_ar' => 'لغة المستندات الافتراضية',
                'options' => ['ar', 'en', 'both'],
                'sort_order' => 2,
            ],
            [
                'key' => 'defaults.quotation_validity_days',
                'value' => '7',
                'type' => 'integer',
                'category' => 'defaults',
                'label_ar' => 'مدة صلاحية العرض (يوم)',
                'sort_order' => 3,
            ],
            [
                'key' => 'defaults.manufacturing_days',
                'value' => '105',
                'type' => 'integer',
                'category' => 'defaults',
                'label_ar' => 'مدة التصنيع الافتراضية (يوم)',
                'sort_order' => 4,
            ],
            [
                'key' => 'defaults.warranty_months',
                'value' => '12',
                'type' => 'integer',
                'category' => 'defaults',
                'label_ar' => 'مدة الضمان للتصنيع (شهر)',
                'sort_order' => 5,
            ],
            [
                'key' => 'defaults.warranty_years_steel',
                'value' => '12',
                'type' => 'integer',
                'category' => 'defaults',
                'label_ar' => 'مدة الضمان للصاج والسلك (سنة)',
                'sort_order' => 6,
            ],
            [
                'key' => 'defaults.spare_parts_years',
                'value' => '12',
                'type' => 'integer',
                'category' => 'defaults',
                'label_ar' => 'مدة توفر قطع الغيار (سنة)',
                'sort_order' => 7,
            ],
            [
                'key' => 'defaults.payment_advance_percentage',
                'value' => '70',
                'type' => 'decimal',
                'category' => 'defaults',
                'label_ar' => 'نسبة الدفعة المقدمة الافتراضية %',
                'sort_order' => 8,
            ],
            [
                'key' => 'defaults.penalty_amount_per_day',
                'value' => '10000',
                'type' => 'decimal',
                'category' => 'defaults',
                'label_ar' => 'الشرط الجزائي اليومي',
                'sort_order' => 9,
            ],
            [
                'key' => 'defaults.timezone',
                'value' => 'Africa/Cairo',
                'type' => 'string',
                'category' => 'defaults',
                'label_ar' => 'المنطقة الزمنية',
                'sort_order' => 10,
            ],

            // ═══════════ Category: pdf ═══════════
            [
                'key' => 'pdf.font_family_ar',
                'value' => 'cairo',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'الخط العربي للـ PDF',
                'options' => ['cairo', 'amiri', 'tajawal'],
                'sort_order' => 1,
            ],
            [
                'key' => 'pdf.font_family_en',
                'value' => 'opensans',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'الخط الإنجليزي للـ PDF',
                'sort_order' => 2,
            ],
            [
                'key' => 'pdf.show_watermark',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'pdf',
                'label_ar' => 'إظهار العلامة المائية',
                'sort_order' => 3,
            ],
            [
                'key' => 'pdf.watermark_text',
                'value' => 'MI',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'نص العلامة المائية (لو ما فيش صورة)',
                'sort_order' => 4,
            ],
            [
                'key' => 'pdf.show_qr_code',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'pdf',
                'label_ar' => 'إظهار QR Code في الفوتر',
                'sort_order' => 5,
            ],
            [
                'key' => 'pdf.copyright_text_ar',
                'value' => 'جميع الحقوق محفوظة لشركة إم آي للصناعات المعدنية',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'نص حقوق النشر',
                'sort_order' => 6,
            ],
            [
                'key' => 'pdf.confidentiality_notice_ar',
                'value' => 'هذا المستند سري ولا يجوز نشره',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'تنبيه السرية',
                'sort_order' => 7,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['is_public' => true])
            );
        }
    }

    protected function seedBankAccounts(): void
    {
        CompanyBankAccount::updateOrCreate(
            ['account_number' => '4103171451983401015'],
            [
                'bank_name_ar' => 'البنك الأهلى المصرى',
                'bank_name_en' => 'National Bank of Egypt',
                'account_name_ar' => 'إم آي للصناعات المعدنية',
                'account_name_en' => 'MI for Metal Industry',
                'currency' => 'EGP',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}
