<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentScheduler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->seedUsers();
        $this->call(CompanySettingsSeeder::class);
        $this->call(LeadSourcesSeeder::class);
        $this->seedContractTypes();
        $this->seedProducts();
        $this->seedContractClauses();
        $this->seedSampleContract();
        $this->call(QuotationSeeder::class);
        $this->call(PoultryPricingSettingsSeeder::class);
        $this->call(TaxAndFinanceSettingsSeeder::class);
    }

    protected function seedUsers(): void
    {
        // Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@mi-cnc.com'],
            [
                'name' => 'محمد مأمون',
                'password' => Hash::make('password'),
                'phone' => '+201026253004',
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');

        // Sales Manager
        $salesManager = User::updateOrCreate(
            ['email' => 'manager@mi-cnc.com'],
            [
                'name' => 'مدير المبيعات',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $salesManager->assignRole('sales_manager');

        // Sales Reps (2)
        $sales1 = User::updateOrCreate(
            ['email' => 'sales1@mi-cnc.com'],
            [
                'name' => 'أحمد - مندوب مبيعات',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $sales1->assignRole('sales_rep');

        $sales2 = User::updateOrCreate(
            ['email' => 'sales2@mi-cnc.com'],
            [
                'name' => 'محمود - مندوب مبيعات',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $sales2->assignRole('sales_rep');

        // Accountant
        $accountant = User::updateOrCreate(
            ['email' => 'accountant@mi-cnc.com'],
            [
                'name' => 'المحاسب',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $accountant->assignRole('accountant');
    }

    protected function seedContractTypes(): void
    {
        $defaultPaymentSchedule = [
            ['description' => 'الدفعة المقدمة (70%) - عند التوقيع', 'percentage' => 70, 'milestone_code' => 'CONTRACT_SIGN'],
            ['description' => 'الدفعة الثانية (25%) - عند بدء الشحن', 'percentage' => 25, 'milestone_code' => 'SHIPPING_START'],
            ['description' => 'الدفعة الأخيرة (5%) - عند التسليم', 'percentage' => 5, 'milestone_code' => 'FINAL_DELIVERY'],
        ];

        $types = [
            [
                'code' => 'FATTENING_FULL',
                'name' => 'تسمين دواجن - مشروع كامل',
                'name_en' => 'Full Fattening Project',
                'icon' => 'heroicon-o-home',
                'color' => 'primary',
                'description' => 'مشروع كامل: بطاريات + إنشاءات + كهرباء + سباكة + تجهيزات',
                'payment_schedule_default' => $defaultPaymentSchedule,
                'sort_order' => 1,
            ],
            [
                'code' => 'LAYING_FULL',
                'name' => 'بياض دواجن - مشروع كامل',
                'name_en' => 'Full Laying Project',
                'icon' => 'heroicon-o-home',
                'color' => 'warning',
                'description' => 'مشروع بياض كامل بكل المشتملات',
                'payment_schedule_default' => $defaultPaymentSchedule,
                'sort_order' => 2,
            ],
            [
                'code' => 'CAGES_ONLY',
                'name' => 'بطاريات فقط',
                'name_en' => 'Cages Only',
                'icon' => 'heroicon-o-cube',
                'color' => 'info',
                'description' => 'توريد وتركيب البطاريات فقط بدون إنشاءات',
                'payment_schedule_default' => $defaultPaymentSchedule,
                'sort_order' => 3,
            ],
            [
                'code' => 'CONSTRUCTION_ONLY',
                'name' => 'إنشاءات فقط',
                'name_en' => 'Construction Only',
                'icon' => 'heroicon-o-wrench',
                'color' => 'gray',
                'description' => 'أعمال إنشاءات وحوائط وأرضيات فقط',
                'payment_schedule_default' => [
                    ['description' => 'الدفعة المقدمة (50%)', 'percentage' => 50, 'milestone_code' => 'CONTRACT_SIGN'],
                    ['description' => 'الدفعة الثانية (40%)', 'percentage' => 40, 'milestone_code' => 'INSTALLATION_START'],
                    ['description' => 'الدفعة الأخيرة (10%)', 'percentage' => 10, 'milestone_code' => 'FINAL_DELIVERY'],
                ],
                'sort_order' => 4,
            ],
            [
                'code' => 'MAINTENANCE',
                'name' => 'صيانة سنوية',
                'name_en' => 'Annual Maintenance',
                'icon' => 'heroicon-o-cog',
                'color' => 'success',
                'description' => 'عقد صيانة دورية',
                'payment_schedule_default' => [
                    ['description' => 'الدفعة الكاملة عند التوقيع', 'percentage' => 100, 'milestone_code' => 'CONTRACT_SIGN'],
                ],
                'sort_order' => 5,
            ],
            [
                'code' => 'SPARE_PARTS',
                'name' => 'توريد قطع غيار',
                'name_en' => 'Spare Parts Supply',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'gray',
                'description' => 'توريد قطع غيار وأكسسوارات',
                'payment_schedule_default' => [
                    ['description' => 'دفعة مقدمة (60%)', 'percentage' => 60, 'milestone_code' => 'CONTRACT_SIGN'],
                    ['description' => 'دفعة عند الاستلام (40%)', 'percentage' => 40, 'milestone_code' => 'FINAL_DELIVERY'],
                ],
                'sort_order' => 6,
            ],
        ];

        foreach ($types as $type) {
            ContractType::updateOrCreate(['code' => $type['code']], $type);
        }
    }

    protected function seedProducts(): void
    {
        $products = [
            ['code' => 'PROD-00001', 'name' => 'بطاريات تسمين 4 أدوار - عنبر كامل', 'category' => 'cages', 'unit' => 'hall', 'standard_price' => 5600000, 'technical_specs' => '2,304 قفص، 41,472 طائر، شامل سايلو 11 طن'],
            ['code' => 'PROD-00002', 'name' => 'بطاريات تسمين 3 أدوار - عنبر كامل', 'category' => 'cages', 'unit' => 'hall', 'standard_price' => 4181925, 'technical_specs' => '1,728 قفص، 27,648 طائر، شامل سايلو 11 طن'],
            ['code' => 'PROD-00003', 'name' => 'أعمال خرسانية (سيمالت + أرضيات)', 'category' => 'construction', 'unit' => 'sqm', 'standard_price' => 1200, 'technical_specs' => 'شامل الحفر والتسليح والهليكوبتر'],
            ['code' => 'PROD-00004', 'name' => 'هيكل معدني واستيل', 'category' => 'construction', 'unit' => 'sqm', 'standard_price' => 2500, 'technical_specs' => 'شامل التند والشاسيهات والصاج المعرج وصوف العزل'],
            ['code' => 'PROD-00005', 'name' => 'حوائط طوب أسمنتي مزدوجة', 'category' => 'construction', 'unit' => 'sqm', 'standard_price' => 1200, 'technical_specs' => 'جداران بفراغ 5 سم + محارة عازلة'],
            ['code' => 'PROD-00006', 'name' => 'خزانات مياه (شرب/أدوية/تبريد/سولار)', 'category' => 'plumbing', 'unit' => 'set', 'standard_price' => 400000],
            ['code' => 'PROD-00007', 'name' => 'شفاط رئيسي MUNTER ITALY EM50', 'category' => 'ventilation', 'unit' => 'piece', 'standard_price' => 34000, 'technical_specs' => '140×140 سم شامل الكابلات'],
            ['code' => 'PROD-00008', 'name' => 'شفاط جانبي MUNTER ITALY EM36', 'category' => 'ventilation', 'unit' => 'piece', 'standard_price' => 32000, 'technical_specs' => '100×100 سم'],
            ['code' => 'PROD-00009', 'name' => 'خلايا تبريد ورق الوادي 15 سم', 'category' => 'cooling', 'unit' => 'sqm', 'standard_price' => 4800, 'technical_specs' => 'شامل السباكة والطلمبات'],
            ['code' => 'PROD-00010', 'name' => 'دفاية ممدوح خليفة', 'category' => 'heating', 'unit' => 'piece', 'standard_price' => 140000, 'technical_specs' => '130 م³/ساعة، قلب استانلس'],
            ['code' => 'PROD-00011', 'name' => 'شباك تهوية INLET TURKEY', 'category' => 'ventilation', 'unit' => 'piece', 'standard_price' => 2300, 'technical_specs' => '55×25×15 سم شامل الونش الكهربائي'],
            ['code' => 'PROD-00012', 'name' => 'MUNTER TRIO 20 - وحدة تحكم', 'category' => 'control', 'unit' => 'set', 'standard_price' => 290000, 'technical_specs' => 'للتحكم بكامل العنبر أوتوماتيك'],
            ['code' => 'PROD-00013', 'name' => 'لوح كنترول والإنارة - شامل الكابلات', 'category' => 'electricity', 'unit' => 'set', 'standard_price' => 700000, 'technical_specs' => 'كابلات وحوامل والونش الكهربائي'],
            ['code' => 'PROD-00014', 'name' => 'جنريتور احتياطي 100 KVA', 'category' => 'generator', 'unit' => 'piece', 'standard_price' => 850000],
            ['code' => 'PROD-00015', 'name' => 'نظام إطفاء حريق', 'category' => 'fire_system', 'unit' => 'set', 'standard_price' => 350000],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['code' => $p['code']], $p + ['currency' => 'EGP', 'is_active' => true]);
        }
    }

    protected function seedContractClauses(): void
    {
        $clauses = [
            // ============ ديباجة ============
            [
                'code' => 'CL-PREAMBLE-FATTENING',
                'title' => 'ديباجة عقد التسمين',
                'category' => 'preamble',
                'content' => 'إنه في يوم {{CONTRACT_DAY_NAME}} الموافق {{CONTRACT_DATE}}، تم إبرام هذا العقد بين الطرفين أعلاه. وحيث أن الطرف الأول شركة متخصصة في تصنيع وتوريد أنظمة بطاريات الدواجن الأوتوماتيكية ومعدات مزارع الدواجن الحديثة، والطرف الثاني راغب في شراء عدد ({{HALL_COUNT}}) من عنابر بطاريات الدواجن الأوتوماتيكية لمزرعته. وقد أقر الطرفان بأهليتهما القانونية الكاملة للتعاقد، فقد اتفقا على ما يلي:',
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],

            // ============ الإنشاءات ============
            [
                'code' => 'CL-CONSTRUCTION',
                'title' => 'بند الإنشاءات والأعمال المدنية',
                'category' => 'construction',
                'content' => 'يلتزم الطرف الأول بتنفيذ الأعمال الإنشائية للعنبر بمواصفات عالية الجودة مطابقة للمواصفات القياسية المصرية، وتشمل الأعمال التالية:

[[ITEMS_TABLE]]

تكلفة هذا البند: {{CONSTRUCTION_TOTAL_COST}} {{CURRENCY}}.

ضمان الإنشاءات لمدة {{CONSTRUCTION_WARRANTY_YEARS}} سنة من تاريخ التسليم.',
                'variables' => [
                    ['name' => 'CONSTRUCTION_TOTAL_COST', 'label' => 'إجمالي تكلفة الإنشاءات', 'type' => 'money', 'required' => true],
                    ['name' => 'CONSTRUCTION_WARRANTY_YEARS', 'label' => 'سنوات ضمان الإنشاءات', 'type' => 'number', 'default' => '5'],
                ],
                'items_schema' => [
                    ['key' => 'item', 'label' => 'البند', 'type' => 'text'],
                    ['key' => 'qty', 'label' => 'الكمية', 'type' => 'number'],
                    ['key' => 'unit', 'label' => 'الوحدة', 'type' => 'text'],
                    ['key' => 'price', 'label' => 'سعر الوحدة', 'type' => 'money'],
                    ['key' => 'total', 'label' => 'الإجمالي', 'type' => 'money', 'sum_total' => true],
                ],
                'is_default' => false,
                'sort_order' => 5,
            ],

            // ============ الكهرباء ============
            [
                'code' => 'CL-ELECTRICITY',
                'title' => 'بند الكهرباء والتمديدات',
                'category' => 'electricity',
                'content' => 'يلتزم الطرف الأول بتنفيذ الأعمال الكهربائية بمواصفات عالية الجودة طبقاً للكود الكهربائي المصري، وتشمل:

[[ITEMS_TABLE]]

- لوحة كنترول رئيسية مع كل وسائل الحماية
- كابلات نحاسية معتمدة من الجهات الرسمية
- إنارة LED موفرة للطاقة
- تأريض كهربائي كامل

تكلفة هذا البند: {{ELECTRICITY_TOTAL_COST}} {{CURRENCY}}.
الحمل الكهربائي الإجمالي: {{TOTAL_ELECTRIC_LOAD}} كيلو وات.
ضمان الكهرباء لمدة {{ELECTRICITY_WARRANTY_YEARS}} سنة.',
                'variables' => [
                    ['name' => 'ELECTRICITY_TOTAL_COST', 'label' => 'إجمالي تكلفة الكهرباء', 'type' => 'money', 'required' => true],
                    ['name' => 'TOTAL_ELECTRIC_LOAD', 'label' => 'الحمل الكلي (KW)', 'type' => 'number', 'default' => '50'],
                    ['name' => 'ELECTRICITY_WARRANTY_YEARS', 'label' => 'سنوات الضمان', 'type' => 'number', 'default' => '2'],
                ],
                'items_schema' => [
                    ['key' => 'item', 'label' => 'البند', 'type' => 'text'],
                    ['key' => 'qty', 'label' => 'الكمية', 'type' => 'number'],
                    ['key' => 'unit', 'label' => 'الوحدة', 'type' => 'text'],
                    ['key' => 'price', 'label' => 'السعر', 'type' => 'money'],
                    ['key' => 'total', 'label' => 'الإجمالي', 'type' => 'money', 'sum_total' => true],
                ],
                'sort_order' => 6,
            ],

            // ============ السباكة ============
            [
                'code' => 'CL-PLUMBING',
                'title' => 'بند السباكة والمياه',
                'category' => 'plumbing',
                'content' => 'يلتزم الطرف الأول بتنفيذ أعمال السباكة بمواصفات قياسية، وتشمل:

[[ITEMS_TABLE]]

- مواسير PPR للمياه الباردة
- خزانات مياه شرب وأدوية وتبريد وسولار
- شبكة توزيع كاملة لكل أدوار العنبر
- مضخات ضغط

تكلفة هذا البند: {{PLUMBING_TOTAL_COST}} {{CURRENCY}}.',
                'variables' => [
                    ['name' => 'PLUMBING_TOTAL_COST', 'label' => 'إجمالي تكلفة السباكة', 'type' => 'money', 'required' => true],
                ],
                'items_schema' => [
                    ['key' => 'item', 'label' => 'البند', 'type' => 'text'],
                    ['key' => 'qty', 'label' => 'الكمية', 'type' => 'number'],
                    ['key' => 'unit', 'label' => 'الوحدة', 'type' => 'text'],
                    ['key' => 'price', 'label' => 'السعر', 'type' => 'money'],
                    ['key' => 'total', 'label' => 'الإجمالي', 'type' => 'money', 'sum_total' => true],
                ],
                'sort_order' => 7,
            ],

            // ============ التركيب ============
            [
                'code' => 'CL-INSTALLATION',
                'title' => 'التركيب والتشغيل التجريبي',
                'category' => 'installation',
                'content' => '1. التركيب: يلتزم الطرف الأول بإرسال مشرف فني متخصص لتركيب البطاريات. يتحمل الطرف الأول تكاليف العمالة، ويلتزم الطرف الثاني بتوفير: مياه شرب، أماكن نوم تكفي {{WORKERS_COUNT}} أفراد، مصدر للطهي، مبرد مياه، مساحة عمل آمنة.

2. التشغيل التجريبي: يقوم الطرف الأول بتشغيل تجريبي لمدة {{TESTING_DAYS}} يوم للتأكد من الكفاءة.

3. التدريب: يقدم الطرف الأول تدريباً عملياً لعمالة الطرف الثاني على التشغيل والصيانة لمدة {{TRAINING_DAYS}} أيام.',
                'variables' => [
                    ['name' => 'WORKERS_COUNT', 'label' => 'عدد العمال', 'type' => 'number', 'default' => '8'],
                    ['name' => 'TESTING_DAYS', 'label' => 'أيام التشغيل التجريبي', 'type' => 'number', 'default' => '15'],
                    ['name' => 'TRAINING_DAYS', 'label' => 'أيام التدريب', 'type' => 'number', 'default' => '5'],
                ],
                'is_default' => true,
                'sort_order' => 10,
            ],

            // ============ الضمان ============
            [
                'code' => 'CL-WARRANTY',
                'title' => 'الضمان وقطع الغيار',
                'category' => 'warranty',
                'content' => 'أولاً: مدة الضمان
- الصاج والسلك: ضد الصدأ والتآكل لمدة ({{STEEL_WARRANTY_YEARS}}) سنة من تاريخ التصنيع.
- عيوب التصنيع: لمدة ({{MANUFACTURING_WARRANTY_MONTHS}}) شهراً من تاريخ التركيب.
- الإكسسوارات: تخضع لضمانها الأصلي من الشركات المصنعة.

ثانياً: حالات سقوط الضمان
- تعديل المنتج دون موافقة كتابية من الطرف الأول.
- تركيب أو صيانة المنتج بواسطة شركة أخرى.
- غسل الصاج بمواد كيميائية ضارة أو مياه مالحة.
- سوء الاستخدام أو مخالفة التعليمات.

ثالثاً: قطع الغيار
يلتزم الطرف الأول بتوفير قطع الغيار لمدة ({{SPARE_PARTS_YEARS}}) سنة من تاريخ التصنيع.',
                'variables' => [
                    ['name' => 'STEEL_WARRANTY_YEARS', 'label' => 'سنوات ضمان الصاج', 'type' => 'number', 'default' => '12'],
                    ['name' => 'MANUFACTURING_WARRANTY_MONTHS', 'label' => 'شهور ضمان التصنيع', 'type' => 'number', 'default' => '12'],
                    ['name' => 'SPARE_PARTS_YEARS', 'label' => 'سنوات توفر قطع الغيار', 'type' => 'number', 'default' => '12'],
                ],
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 15,
            ],

            // ============ الجنريتور ============
            [
                'code' => 'CL-GENERATOR',
                'title' => 'الجنريتور الاحتياطي',
                'category' => 'generator',
                'content' => 'يلتزم الطرف الأول بتوريد وتركيب جنريتور احتياطي بقدرة ({{GENERATOR_KVA}}) كيلو فولت أمبير، يعمل أوتوماتيكياً عند انقطاع التيار الكهربائي، ويغطي كامل أحمال العنبر لمدة لا تقل عن ({{BACKUP_HOURS}}) ساعة متواصلة.

تكلفة الجنريتور: {{GENERATOR_COST}} {{CURRENCY}} شامل التركيب والتشغيل والضمان لمدة سنتين.',
                'variables' => [
                    ['name' => 'GENERATOR_KVA', 'label' => 'قدرة الجنريتور (KVA)', 'type' => 'number', 'default' => '100'],
                    ['name' => 'BACKUP_HOURS', 'label' => 'ساعات التشغيل الاحتياطي', 'type' => 'number', 'default' => '12'],
                    ['name' => 'GENERATOR_COST', 'label' => 'تكلفة الجنريتور', 'type' => 'money', 'required' => true],
                ],
                'sort_order' => 8,
            ],

            // ============ نظام الإطفاء ============
            [
                'code' => 'CL-FIRE-SAFETY',
                'title' => 'نظام السلامة والإطفاء',
                'category' => 'fire_safety',
                'content' => 'يلتزم الطرف الأول بتوريد وتركيب نظام إطفاء حريق آلي طبقاً لمواصفات الدفاع المدني، ويشمل:
- طفايات حريق متنوعة (CO2 + بودرة جافة) عدد ({{EXTINGUISHERS_COUNT}}) طفاية.
- نظام رش مياه أوتوماتيكي (Sprinkler) إن لزم.
- كاشفات دخان وحرارة في كل أركان العنبر.
- لوحة تحكم مركزية مع إنذار صوتي.

تكلفة هذا النظام: {{FIRE_SYSTEM_COST}} {{CURRENCY}} شامل التركيب والاختبار.',
                'variables' => [
                    ['name' => 'EXTINGUISHERS_COUNT', 'label' => 'عدد طفايات الحريق', 'type' => 'number', 'default' => '6'],
                    ['name' => 'FIRE_SYSTEM_COST', 'label' => 'تكلفة نظام الإطفاء', 'type' => 'money', 'required' => true],
                ],
                'sort_order' => 9,
            ],

            // ============ غرامات التأخير ============
            [
                'code' => 'CL-PENALTIES',
                'title' => 'الشروط الجزائية وغرامات التأخير',
                'category' => 'penalties',
                'content' => 'في حال تأخر الطرف الثاني عن سداد أي دفعة في موعدها، يحق للطرف الأول تأخير موعد التسليم بنفس فترة التأخير دون أي مسؤولية. وفي حالة الإخلال الجسيم بأي بند من بنود هذا العقد، يلتزم الطرف المخل بشرط جزائي قدره ({{PENALTY_AMOUNT}}) {{CURRENCY}} عن كل يوم تأخير، بحد أقصى ({{MAX_PENALTY_DAYS}}) يوم، يحق بعدها للطرف المتضرر فسخ العقد والمطالبة بكامل التعويضات.',
                'variables' => [
                    ['name' => 'PENALTY_AMOUNT', 'label' => 'قيمة الغرامة اليومية', 'type' => 'money', 'default' => '10000'],
                    ['name' => 'MAX_PENALTY_DAYS', 'label' => 'الحد الأقصى لأيام التأخير', 'type' => 'number', 'default' => '30'],
                ],
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 16,
            ],

            // ============ السرية ============
            [
                'code' => 'CL-CONFIDENTIALITY',
                'title' => 'بند السرية',
                'category' => 'confidentiality',
                'content' => 'يلتزم الطرفان بسرية المعلومات الفنية والتجارية والمالية المتبادلة بينهما خلال سريان هذا العقد ولمدة ({{CONFIDENTIALITY_YEARS}}) سنوات بعد انتهائه. ويُحظر على أي طرف الإفصاح عن أي معلومة لطرف ثالث دون موافقة كتابية مسبقة من الطرف الآخر، تحت طائلة المسؤولية القانونية والتعويض.',
                'variables' => [
                    ['name' => 'CONFIDENTIALITY_YEARS', 'label' => 'سنوات السرية بعد العقد', 'type' => 'number', 'default' => '5'],
                ],
                'is_default' => true,
                'sort_order' => 17,
            ],

            // ============ الاختصاص القضائي ============
            [
                'code' => 'CL-JURISDICTION',
                'title' => 'الاختصاص القضائي',
                'category' => 'jurisdiction',
                'content' => 'في حال نشوء أي نزاع، يسعى الطرفان للتسوية الودية خلال ({{NEGOTIATION_DAYS}}) أيام عمل. وفي حال التعذر، تكون {{COURT_LOCATION}} صاحبة الاختصاص بنظر النزاع. ويخضع هذا العقد لأحكام القوانين المعمول بها في {{LEGAL_JURISDICTION}}.',
                'variables' => [
                    ['name' => 'NEGOTIATION_DAYS', 'label' => 'أيام التفاوض الودي', 'type' => 'number', 'default' => '3'],
                    ['name' => 'COURT_LOCATION', 'label' => 'المحكمة المختصة', 'type' => 'text', 'default' => 'المحاكم المصرية'],
                    ['name' => 'LEGAL_JURISDICTION', 'label' => 'الاختصاص القانوني', 'type' => 'text', 'default' => 'جمهورية مصر العربية'],
                ],
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 18,
            ],

            // ============ القوة القاهرة ============
            [
                'code' => 'CL-FORCE-MAJEURE',
                'title' => 'القوة القاهرة',
                'category' => 'force_majeure',
                'content' => 'لا يُعد أي طرف مسؤولاً عن أي تأخير أو إخلال ناتج عن ظروف قاهرة خارجة عن إرادته، مثل الكوارث الطبيعية، الحروب، الأوبئة، قرارات السلطات الحكومية المؤثرة. يلتزم الطرف المتضرر بإبلاغ الطرف الآخر خلال ({{NOTIFY_DAYS}}) أيام من حدوث الواقعة.',
                'variables' => [
                    ['name' => 'NOTIFY_DAYS', 'label' => 'أيام الإبلاغ', 'type' => 'number', 'default' => '7'],
                ],
                'is_default' => true,
                'sort_order' => 19,
            ],

            // ============ الصيانة ============
            [
                'code' => 'CL-MAINTENANCE',
                'title' => 'الصيانة الدورية',
                'category' => 'maintenance',
                'content' => 'يلتزم الطرف الأول بتقديم خدمات الصيانة الدورية بعد انتهاء فترة الضمان وفق العقد التالي:
- زيارة صيانة دورية كل ({{VISIT_INTERVAL_MONTHS}}) أشهر.
- زيارات طارئة عند الحاجة خلال ({{EMERGENCY_RESPONSE_HOURS}}) ساعة من الإبلاغ.
- تكلفة الصيانة السنوية: {{MAINTENANCE_ANNUAL_COST}} {{CURRENCY}}.
- لا تشمل قطع الغيار التي تُحاسب منفصلة بأسعار التكلفة.',
                'variables' => [
                    ['name' => 'VISIT_INTERVAL_MONTHS', 'label' => 'دورية الزيارات (شهر)', 'type' => 'number', 'default' => '3'],
                    ['name' => 'EMERGENCY_RESPONSE_HOURS', 'label' => 'وقت الاستجابة (ساعة)', 'type' => 'number', 'default' => '24'],
                    ['name' => 'MAINTENANCE_ANNUAL_COST', 'label' => 'تكلفة الصيانة السنوية', 'type' => 'money'],
                ],
                'sort_order' => 14,
            ],

            // ============ التدريب ============
            [
                'code' => 'CL-TRAINING-EXTENDED',
                'title' => 'التدريب الموسّع للعمالة',
                'category' => 'training',
                'content' => 'يقدم الطرف الأول برنامج تدريبي موسّع لعمالة الطرف الثاني، يشمل:
- التشغيل اليومي للبطاريات وأنظمة التحكم.
- الصيانة الدورية والوقائية.
- التعامل مع الأعطال الشائعة.
- معايير سلامة الطيور والإنتاج.

مدة البرنامج: ({{TRAINING_DAYS_EXT}}) أيام عمل.
عدد المتدربين: ({{TRAINEES_COUNT}}) فرداً.
تكلفة التدريب الإضافي: {{EXTENDED_TRAINING_COST}} {{CURRENCY}}.',
                'variables' => [
                    ['name' => 'TRAINING_DAYS_EXT', 'label' => 'أيام التدريب', 'type' => 'number', 'default' => '7'],
                    ['name' => 'TRAINEES_COUNT', 'label' => 'عدد المتدربين', 'type' => 'number', 'default' => '5'],
                    ['name' => 'EXTENDED_TRAINING_COST', 'label' => 'تكلفة التدريب', 'type' => 'money', 'default' => '0'],
                ],
                'sort_order' => 11,
            ],

            // ============ موضوع العقد ============
            [
                'code' => 'CL-SUBJECT-FATTENING',
                'title' => 'موضوع العقد - تسمين',
                'category' => 'subject',
                'content' => 'يلتزم الطرف الأول بتصنيع وتوريد وتركيب للطرف الثاني عدد ({{HALL_COUNT}}) عنبر من بطاريات الدواجن الأوتوماتيكية لمشروع التسمين بالمواصفات التالية:

- اسم المشروع: {{PROJECT_NAME}}
- أبعاد العنبر: {{HALL_DIMENSIONS}}
- عدد الأقفاص: {{CAGE_COUNT}} قفص
- السعة: {{BIRD_CAPACITY}} طائر (لوزن 2.1 كجم لكل طائر)
- موقع التركيب: {{INSTALLATION_LOCATION}}',
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 2,
            ],

            // ============ المالي ============
            [
                'code' => 'CL-FINANCIAL',
                'title' => 'قيمة العقد وطريقة السداد',
                'category' => 'financial',
                'content' => 'أولاً: إجمالي قيمة العقد
اتفق الطرفان على أن إجمالي قيمة هذا العقد مبلغ وقدره ({{TOTAL_VALUE}} {{CURRENCY}}) ({{TOTAL_VALUE_WORDS}}).

ثانياً: طريقة السداد - مرتبطة بالمراحل (Milestones) وليس تواريخ ثابتة:
- الدفعة المقدمة (70%): {{PAYMENT_1_AMOUNT}} {{CURRENCY}} عند توقيع العقد.
- الدفعة الثانية (25%): {{PAYMENT_2_AMOUNT}} {{CURRENCY}} عند بدء الشحن للموقع.
- الدفعة الأخيرة (5%): {{PAYMENT_3_AMOUNT}} {{CURRENCY}} عند اعتماد محضر التسليم النهائي.

ثالثاً: وسيلة الدفع
يتم سداد الدفعات عن طريق تحويل بنكي لحساب الطرف الأول:
- البنك: البنك الأهلي المصري
- رقم الحساب: 4103171451983401015
- اسم الحساب: MI for metal industry',
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 3,
            ],

            // ============ الجدول الزمني ============
            [
                'code' => 'CL-SCHEDULE',
                'title' => 'الجدول الزمني للتسليم',
                'category' => 'schedule',
                'content' => '- مدة التصنيع: ({{MANUFACTURING_DAYS}}) يوم عمل تبدأ من تاريخ سداد الدفعة المقدمة.
- تاريخ التسليم المتوقع: {{EXPECTED_DELIVERY_DATE}}.
- مكان التسليم: {{INSTALLATION_LOCATION}}.
- يتحمل الطرف الأول تكلفة الشحن وتبعة الهلاك حتى التسليم النهائي بالموقع.',
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($clauses as $clause) {
            ContractClause::updateOrCreate(
                ['code' => $clause['code']],
                array_merge($clause, ['is_active' => true])
            );
        }
    }

    protected function seedSampleContract(): void
    {
        // العميل: أحمد نزار من العقد المرفق
        $customer = Customer::updateOrCreate(
            ['national_id' => '801927666'],
            [
                'name' => 'أحمد نزار عدنان اليازجي',
                'nationality' => 'سعودي',
                'phone' => '+966500000000',
                'address' => 'المملكة العربية السعودية',
                'country' => 'SA',
                'type' => 'individual',
                'status' => 'active',
            ]
        );

        $type = ContractType::where('code', 'FATTENING_FULL')->first();

        $contract = Contract::updateOrCreate(
            ['project_code' => 'PRJ-AN-2026'],
            [
                'customer_id' => $customer->id,
                'contract_type_id' => $type->id,
                'contract_date' => '2026-04-25',
                'project_name' => 'مشروع تسمين دواجن - أحمد نزار',
                'project_description' => 'تجهيز مشتملات وبطاريات دواجن أوتوماتيك',
                'installation_location' => 'الإسماعيلية - سرابيوم',
                'hall_length' => 80,
                'hall_width' => 12,
                'hall_height' => 3,
                'hall_count' => 1,
                'cage_count' => 1728,
                'bird_capacity' => 27648,
                'cages_cost' => 4181925,
                'accessories_cost' => 1634500,
                'discount_amount' => 316425,
                'currency' => 'EGP',
                'manufacturing_days' => 105,
                'status' => 'signed',
                'payment_status' => 'partially_paid',
            ]
        );

        // ربط البنود الافتراضية
        $defaultClauses = ContractClause::where('is_default', true)->orderBy('sort_order')->get();
        foreach ($defaultClauses as $idx => $clause) {
            $contract->clauses()->syncWithoutDetaching([
                $clause->id => ['sort_order' => $idx, 'is_visible' => true],
            ]);
        }

        // توليد الدفعات
        try {
            app(PaymentScheduler::class)->generateForContract($contract->fresh());
        } catch (\Throwable $e) {
            // تجاهل لو حدث خطأ
        }
    }
}
