<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ImageLibrary;
use App\Models\Quotation;
use App\Models\QuotationSection;
use App\Models\QuotationTerm;
use App\Models\QuotationType;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedQuotationTypes();
        $this->seedImageLibrary();
        $this->seedQuotationSections();
        $this->seedQuotationTerms();
        $this->linkSectionImages();
        $this->seedSampleQuotation();
    }

    protected function seedSampleQuotation(): void
    {
        $customer = Customer::first();
        $type = QuotationType::where('code', 'FULL_PROJECT')->first() ?? QuotationType::first();
        if (! $customer || ! $type) {
            return;
        }

        $q = Quotation::create([
            'customer_id' => $customer->id,
            'quotation_type_id' => $type->id,
            'created_by' => User::first()?->id,
            'status' => 'draft',
            'quotation_date' => now(),
            'valid_until' => now()->addDays(7),
            'validity_period_days' => 7,
            'project_name' => 'عينة اختبار — بند بسعر 500,000 ج.م',
            'project_description' => 'عرض تجريبي لاختبار PDF والحقول المالية',
            'installation_location' => 'دمياط',
            'hall_type' => 'تسمين',
            'hall_count' => 1,
            'language' => 'both',
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'vat_percentage' => 15,
            'discount_percentage' => 0,
            'discount_amount' => 0,
        ]);

        $q->items()->create([
            'description_ar' => 'بند تجريبي — سعر الوحدة',
            'description_en' => 'Sample line item',
            'unit_price' => 500000,
            'quantity' => 1,
            'unit' => 'piece',
            'discount_percentage' => 0,
            'is_taxable' => true,
            'sort_order' => 0,
        ]);

        $q->save();
    }

    protected function seedQuotationTypes(): void
    {
        $types = [
            [
                'code' => 'CONSTRUCTION_ONLY',
                'name' => 'إنشاءات فقط',
                'name_en' => 'Construction Only',
                'description' => 'أعمال إنشاءات معدنية ومدنية فقط بدون بطاريات أو مشتملات',
                'icon' => 'heroicon-o-wrench',
                'color' => 'gray',
                'default_sections' => [],
                'default_terms' => [],
                'default_payment_schedule' => [50, 50],
                'default_validity_days' => 7,
                'sort_order' => 1,
            ],
            [
                'code' => 'CAGES_ONLY',
                'name' => 'بطاريات فقط',
                'name_en' => 'Cages Only',
                'description' => 'توريد وتركيب بطاريات دواجن فقط (Broiler/Layer)',
                'icon' => 'heroicon-o-cube',
                'color' => 'info',
                'default_sections' => [],
                'default_terms' => [],
                'default_payment_schedule' => [70, 30],
                'default_validity_days' => 7,
                'sort_order' => 2,
            ],
            [
                'code' => 'FULL_PROJECT',
                'name' => 'مشروع كامل',
                'name_en' => 'Full Project',
                'description' => 'مشروع متكامل: إنشاءات + بطاريات + مشتملات + تهوية + تبريد + كهرباء',
                'icon' => 'heroicon-o-home',
                'color' => 'primary',
                'default_sections' => [],
                'default_terms' => [],
                'default_payment_schedule' => [70, 25, 5],
                'default_validity_days' => 3,
                'sort_order' => 3,
            ],
            [
                'code' => 'ACCESSORIES_ONLY',
                'name' => 'مشتملات فقط',
                'name_en' => 'Accessories Only',
                'description' => 'تهوية + تبريد + كهرباء + لوحات تحكم فقط',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'success',
                'default_sections' => [],
                'default_terms' => [],
                'default_payment_schedule' => [70, 30],
                'default_validity_days' => 7,
                'sort_order' => 4,
            ],
        ];

        foreach ($types as $type) {
            QuotationType::updateOrCreate(['code' => $type['code']], $type + ['is_active' => true]);
        }
    }

    protected function seedImageLibrary(): void
    {
        $adminId = User::first()?->id ?? 1;

        $images = [
            // steel_work (5)
            ['code' => 'STEEL_FRAME_01', 'title_ar' => 'هيكل معدني - منظر عام', 'title_en' => 'Steel Frame - Overview', 'category' => 'steel_work', 'file_path' => 'images/library/placeholders/steel-frame-01.jpg', 'tags' => ['steel', 'frame', 'structure']],
            ['code' => 'STEEL_FRAME_02', 'title_ar' => 'هيكل معدني - تفاصيل التجميع', 'title_en' => 'Steel Frame - Assembly Details', 'category' => 'steel_work', 'file_path' => 'images/library/placeholders/steel-frame-02.jpg', 'tags' => ['steel', 'assembly', 'bolts']],
            ['code' => 'STEEL_ROOF_01', 'title_ar' => 'سقف معدني - صاج معرج', 'title_en' => 'Steel Roof - Corrugated Sheet', 'category' => 'steel_work', 'file_path' => 'images/library/placeholders/steel-roof-01.jpg', 'tags' => ['roof', 'sheet', 'steel']],
            ['code' => 'STEEL_WALL_01', 'title_ar' => 'حوائط معدنية - صاج ملون', 'title_en' => 'Steel Wall - Color Sheet', 'category' => 'steel_work', 'file_path' => 'images/library/placeholders/steel-wall-01.jpg', 'tags' => ['wall', 'sheet', 'color']],
            ['code' => 'STEEL_INSULATION_01', 'title_ar' => 'عزل حراري - صوف صخري', 'title_en' => 'Thermal Insulation - Rock Wool', 'category' => 'steel_work', 'file_path' => 'images/library/placeholders/steel-insulation-01.jpg', 'tags' => ['insulation', 'wool', 'thermal']],

            // cooling (4)
            ['code' => 'COOLING_PADS_01', 'title_ar' => 'خلايا تبريد - ورق الوادي', 'title_en' => 'Cooling Pads - Cellulose', 'category' => 'cooling', 'file_path' => 'images/library/placeholders/cooling-pads.jpg', 'tags' => ['cooling', 'pads', 'cellulose']],
            ['code' => 'COOLING_PUMP_01', 'title_ar' => 'طلمبة تبريد - دوران المياه', 'title_en' => 'Cooling Pump - Water Circulation', 'category' => 'cooling', 'file_path' => 'images/library/placeholders/cooling-pump.jpg', 'tags' => ['cooling', 'pump', 'water']],
            ['code' => 'COOLING_SHADE_01', 'title_ar' => 'مظلة تبريد - خارجية', 'title_en' => 'Cooling Shade - External', 'category' => 'cooling', 'file_path' => 'images/library/placeholders/cooling-shade.jpg', 'tags' => ['shade', 'cooling', 'external']],
            ['code' => 'COOLING_SYSTEM_01', 'title_ar' => 'نظام تبريد كامل', 'title_en' => 'Complete Cooling System', 'category' => 'cooling', 'file_path' => 'images/library/placeholders/cooling-system.jpg', 'tags' => ['cooling', 'system', 'complete']],

            // ventilation (4)
            ['code' => 'VENT_FAN_MAIN_01', 'title_ar' => 'شفاط رئيسي - MUNTER EM50', 'title_en' => 'Main Exhaust Fan - MUNTER EM50', 'category' => 'ventilation', 'file_path' => 'images/library/placeholders/vent-fan-main.jpg', 'tags' => ['fan', 'exhaust', 'munter']],
            ['code' => 'VENT_FAN_SIDE_01', 'title_ar' => 'شفاط جانبي - MUNTER EM36', 'title_en' => 'Side Exhaust Fan - MUNTER EM36', 'category' => 'ventilation', 'file_path' => 'images/library/placeholders/vent-fan-side.jpg', 'tags' => ['fan', 'side', 'exhaust']],
            ['code' => 'VENT_INLET_01', 'title_ar' => 'شباك تهوية - INLET TURKEY', 'title_en' => 'Air Inlet - INLET TURKEY', 'category' => 'ventilation', 'file_path' => 'images/library/placeholders/vent-inlet.jpg', 'tags' => ['inlet', 'air', 'ventilation']],
            ['code' => 'VENT_TUNNEL_01', 'title_ar' => 'تهوية نفقية - Tunnel Ventilation', 'title_en' => 'Tunnel Ventilation System', 'category' => 'ventilation', 'file_path' => 'images/library/placeholders/vent-tunnel.jpg', 'tags' => ['tunnel', 'ventilation', 'system']],

            // feeding (5)
            ['code' => 'FEED_TROLLEY_01', 'title_ar' => 'عربة تغذية - Feed Trolley', 'title_en' => 'Feed Trolley', 'category' => 'feeding', 'file_path' => 'images/library/placeholders/feed-trolley.jpg', 'tags' => ['feed', 'trolley', 'cage']],
            ['code' => 'FEED_TROUGH_01', 'title_ar' => 'مجرى تغذية - Feed Trough', 'title_en' => 'Feed Trough', 'category' => 'feeding', 'file_path' => 'images/library/placeholders/feed-trough.jpg', 'tags' => ['feed', 'trough', 'cage']],
            ['code' => 'FEED_SENSOR_01', 'title_ar' => 'حساس مستوى العلف', 'title_en' => 'Feed Level Sensor', 'category' => 'feeding', 'file_path' => 'images/library/placeholders/feed-sensor.jpg', 'tags' => ['feed', 'sensor', 'level']],
            ['code' => 'FEED_SILO_01', 'title_ar' => 'خزان علف - Silo 11 طن', 'title_en' => 'Feed Silo - 11 Tons', 'category' => 'feeding', 'file_path' => 'images/library/placeholders/feed-silo.jpg', 'tags' => ['silo', 'feed', 'storage']],
            ['code' => 'FEED_SCREW_01', 'title_ar' => 'سير نقل العلف - Screw Feeder', 'title_en' => 'Screw Feeder', 'category' => 'feeding', 'file_path' => 'images/library/placeholders/feed-screw.jpg', 'tags' => ['screw', 'feeder', 'conveyor']],

            // water (3)
            ['code' => 'WATER_NIPPLE_01', 'title_ar' => 'سقاية نبل - Nipple Drinker', 'title_en' => 'Nipple Drinker System', 'category' => 'water', 'file_path' => 'images/library/placeholders/water-nipple.jpg', 'tags' => ['water', 'nipple', 'drinker']],
            ['code' => 'WATER_TANK_01', 'title_ar' => 'خزان مياه - 10 م³', 'title_en' => 'Water Tank - 10 m³', 'category' => 'water', 'file_path' => 'images/library/placeholders/water-tank.jpg', 'tags' => ['water', 'tank', 'storage']],
            ['code' => 'WATER_REGULATOR_01', 'title_ar' => 'منظم ضغط المياه', 'title_en' => 'Water Pressure Regulator', 'category' => 'water', 'file_path' => 'images/library/placeholders/water-regulator.jpg', 'tags' => ['water', 'pressure', 'regulator']],

            // cages (4)
            ['code' => 'CAGE_BROILER_01', 'title_ar' => 'قفص تسمين - 4 أدوار', 'title_en' => 'Broiler Cage - 4 Tiers', 'category' => 'cages', 'file_path' => 'images/library/placeholders/cage-broiler-4tier.jpg', 'tags' => ['cage', 'broiler', '4-tier']],
            ['code' => 'CAGE_BROILER_02', 'title_ar' => 'قفص تسمين - 3 أدوار', 'title_en' => 'Broiler Cage - 3 Tiers', 'category' => 'cages', 'file_path' => 'images/library/placeholders/cage-broiler-3tier.jpg', 'tags' => ['cage', 'broiler', '3-tier']],
            ['code' => 'CAGE_LAYER_01', 'title_ar' => 'قفص بياض - H-Type', 'title_en' => 'Layer Cage - H-Type', 'category' => 'cages', 'file_path' => 'images/library/placeholders/cage-layer.jpg', 'tags' => ['cage', 'layer', 'h-type']],
            ['code' => 'CAGE_BATTERY_01', 'title_ar' => 'بطارية أقفاص كاملة', 'title_en' => 'Complete Battery Cage System', 'category' => 'cages', 'file_path' => 'images/library/placeholders/cage-battery.jpg', 'tags' => ['battery', 'cage', 'system']],

            // cleaning (3)
            ['code' => 'CLEAN_MANURE_01', 'title_ar' => 'نظام سحب السبلة - حزام', 'title_en' => 'Manure Belt System', 'category' => 'cleaning', 'file_path' => 'images/library/placeholders/clean-manure-belt.jpg', 'tags' => ['manure', 'belt', 'cleaning']],
            ['code' => 'CLEAN_SCRAPER_01', 'title_ar' => 'كاسحة روث - Scraper', 'title_en' => 'Manure Scraper', 'category' => 'cleaning', 'file_path' => 'images/library/placeholders/clean-scraper.jpg', 'tags' => ['scraper', 'manure', 'cleaning']],
            ['code' => 'CLEAN_DISINFECTION_01', 'title_ar' => 'نظام تطهير - رش', 'title_en' => 'Disinfection Spray System', 'category' => 'cleaning', 'file_path' => 'images/library/placeholders/clean-disinfection.jpg', 'tags' => ['disinfection', 'spray', 'cleaning']],

            // civil (4)
            ['code' => 'CIVIL_FOUNDATION_01', 'title_ar' => 'أساسات خرسانية - السيمالت', 'title_en' => 'Concrete Foundation - Semalt', 'category' => 'civil', 'file_path' => 'images/library/placeholders/civil-foundation.jpg', 'tags' => ['civil', 'foundation', 'concrete']],
            ['code' => 'CIVIL_FLOOR_01', 'title_ar' => 'أرضية خرسانية - هليكوبتر', 'title_en' => 'Concrete Floor - Helicopter Finish', 'category' => 'civil', 'file_path' => 'images/library/placeholders/civil-floor.jpg', 'tags' => ['floor', 'concrete', 'helicopter']],
            ['code' => 'CIVIL_BRICK_01', 'title_ar' => 'حوائط طوب - مزدوجة', 'title_en' => 'Double Brick Wall', 'category' => 'civil', 'file_path' => 'images/library/placeholders/civil-brick.jpg', 'tags' => ['brick', 'wall', 'double']],
            ['code' => 'CIVIL_DOOR_01', 'title_ar' => 'باب خدمة - Service Door', 'title_en' => 'Service Door', 'category' => 'civil', 'file_path' => 'images/library/placeholders/civil-door.jpg', 'tags' => ['door', 'service', 'civil']],

            // electrical (4)
            ['code' => 'ELEC_PANEL_01', 'title_ar' => 'لوحة كنترول رئيسية', 'title_en' => 'Main Control Panel', 'category' => 'electrical', 'file_path' => 'images/library/placeholders/elec-panel.jpg', 'tags' => ['panel', 'control', 'electrical']],
            ['code' => 'ELEC_CABLE_01', 'title_ar' => 'كابلات نحاسية - توزيع', 'title_en' => 'Copper Cables - Distribution', 'category' => 'electrical', 'file_path' => 'images/library/placeholders/elec-cable.jpg', 'tags' => ['cable', 'copper', 'electrical']],
            ['code' => 'ELEC_LED_01', 'title_ar' => 'إنارة LED - موفرة', 'title_en' => 'LED Lighting - Energy Saving', 'category' => 'electrical', 'file_path' => 'images/library/placeholders/elec-led.jpg', 'tags' => ['led', 'lighting', 'electrical']],
            ['code' => 'ELEC_CONTROLLER_01', 'title_ar' => 'وحدة تحكم - MUNTER TRIO', 'title_en' => 'Control Unit - MUNTER TRIO', 'category' => 'electrical', 'file_path' => 'images/library/placeholders/elec-controller.jpg', 'tags' => ['controller', 'munter', 'trio']],
        ];

        foreach ($images as $img) {
            ImageLibrary::updateOrCreate(
                ['code' => $img['code']],
                $img + ['uploaded_by' => $adminId]
            );
        }
    }

    protected function seedQuotationSections(): void
    {
        $sections = [
            [
                'code' => 'STEEL_WORK',
                'title_ar' => 'الأعمال المعدنية',
                'title_en' => 'Steel Work',
                'category' => 'technical',
                'content_ar' => "الهيكل المعدني | Steel Structure\n- يتم تصنيع الهيكل المعدني للعنبر من مواصفات عالمية عالية الجودة باستخدام صاج معرج عيار 0.5 مم معالج ضد الصدأ (Galvanized).\n- يتم عمل الباكيات الخاصة بربط الأعمدة والكمرات باستخدام براغي عالية القوة.\n- يتم تركيب العزل الحراري (صوف صخري 5 سم) بين الصاج المعرج لعزل الحرارة والرطوبة.\n- يتم تركيب العزل المائي (ألياف زجاجية) لمنع تسرب المياه.\n\nالسقف | Roof\n- يتم تغطية السقف بصاج معرج ملون سمك 0.5 مم معالج ضد الصدأ.\n- يتم تركيب الشاسيهات الخاصة بالسقف بمسافة 60 سم بين كل شاسيه وآخر.\n- يتم تركيب صوف العزل الحراري بين الصاج والشاسيهات.\n\nالحوائط | Wall\n- يتم إنشاء الحوائط بارتفاع 4 أمتار باستخدام صاج معرج ملون سمك 0.5 مم.\n- يتم تركيب العزل الحراري والمائي داخل الحوائط.\n- يتم تركيب الأبواب والشبابيك الخاصة بالتهوية.",
                'content_en' => "Steel Structure\n- The steel structure is manufactured from high-quality galvanized corrugated sheet (0.5mm).\n- High-strength bolts are used for column and beam connections.\n- Thermal insulation (5cm rock wool) is installed between sheets.\n- Waterproof insulation (fiberglass) prevents water leakage.\n\nRoof\n- Roof covered with colored corrugated galvanized sheet (0.5mm).\n- Roof chassis spaced at 60cm intervals.\n- Thermal insulation installed between sheets and chassis.\n\nWall\n- Walls built to 4m height using colored corrugated sheet (0.5mm).\n- Thermal and waterproof insulation installed inside walls.",
                'sort_order' => 1,
            ],
            [
                'code' => 'CIVIL_WORK',
                'title_ar' => 'الأعمال المدنية',
                'title_en' => 'Civil Work',
                'category' => 'civil',
                'content_ar' => "السيمالت | Foundation\n- يتم حفر السيمالت (الأساسات) بعمق 80 سم وعرض 60 سم حول محيط العنبر.\n- يتم وضع حديد تسليح قطر 12 مم و 16 مم مع شبكة حديد أسفل وأسفل السيمالت.\n- يتم صب الخرسانة المسلحة بسمك 25 سم بمعامل مقاومة 250 كجم/سم².\n- يتم عمل فتحات التهوية والصرف داخل السيمالت.\n\nالأرضيات | Floor\n- يتم عمل أرضية خرسانية مسلحة بسمك 10 سم.\n- يتم استخدام الهليكوبتر لتسوية الأرضية ومنع تراكم المياه.\n- يتم عمل ميول باتجاه فتحات الصرف للتخلص من مياه التطهير.\n- يتم طلاء الأرضية بمادة إيبوكسي مقاومة للأحماض والكيماويات.",
                'content_en' => "Foundation\n- Foundation excavated to 80cm depth and 60cm width around the hall perimeter.\n- Reinforcement steel (12mm and 16mm) with mesh at top and bottom.\n- Reinforced concrete (25cm thickness, 250 kg/cm² strength).\n- Ventilation and drainage openings integrated into foundation.\n\nFloor\n- Reinforced concrete floor (10cm thickness).\n- Helicopter finish for water runoff prevention.\n- Slopes toward drainage openings.\n- Epoxy coating resistant to acids and chemicals.",
                'sort_order' => 2,
            ],
            [
                'code' => 'SERVICE_DOORS',
                'title_ar' => 'أبواب الخدمة',
                'title_en' => 'Service Doors',
                'category' => 'civil',
                'content_ar' => "- يتم تركيب أبواب خدمة مصنعة من الصاج المعرج المجلفن سمك 0.5 مم.\n- أبعاد باب الخدمة: عرض 1.2 متر × ارتفاع 2.2 متر.\n- يتم تركيب إطار معدني مجلفن حول الباب لضمان المتانة.\n- يتم تركيب مفصلات ثقيلة وقفل أمان على كل باب.\n- يتم تركيب شبكة حماية على جزء من الباب للتهوية.",
                'content_en' => "- Service doors made from galvanized corrugated sheet (0.5mm).\n- Door dimensions: 1.2m width × 2.2m height.\n- Galvanized steel frame for durability.\n- Heavy-duty hinges and safety lock on each door.\n- Protective mesh for ventilation installed on part of the door.",
                'sort_order' => 3,
            ],
            [
                'code' => 'COOLING_SHADES',
                'title_ar' => 'المظلات الخاصة بالتبريد',
                'title_en' => 'Cooling Shades',
                'category' => 'cooling',
                'content_ar' => "- يتم تركيب مظلات خارجية فوق جدران العنبر لحجب أشعة الشمس المباشرة.\n- المظلات مصنوعة من قماش PVC معالج مقاوم للأشعة فوق البنفسجية.\n- يتم تركيب هيكل معدني مجلفن لحمل المظلات.\n- المظلات تقلل من درجة حرارة العنبر بمعدل 3-5 درجات مئوية.\n- يتم تركيب نظام شد هيدروليكي لضمان ثبات المظلات في الرياح.",
                'content_en' => "- External shades installed above hall walls to block direct sunlight.\n- Shades made from UV-resistant PVC fabric.\n- Galvanized steel frame supports the shades.\n- Shades reduce hall temperature by 3-5°C.\n- Hydraulic tension system ensures stability in wind.",
                'sort_order' => 4,
            ],
            [
                'code' => 'WATER_TANKS',
                'title_ar' => 'خزانات المياه',
                'title_en' => 'Water Tanks',
                'category' => 'water',
                'content_ar' => "- يتم توريد وتركيب خزانات مياه من الفيبر جلاس سعة 10 م³ لكل نوع.\n- خزان مياه الشرب: 10 م³ مع غطاء محكم ومنع تسرب.\n- خزان مياه الأدوية: 2 م³ مع عزل حراري.\n- خزان مياه التبريد: 5 م³ متصل بنظام خلايا التبريد.\n- خزان السولار: 5 م³ (في حالة استخدام دفايات سولار).\n- جميع الخزانات مزودة بمؤشر مستوى إلكتروني.",
                'content_en' => "- Fiberglass water tanks (10 m³ capacity each type).\n- Drinking water tank: 10 m³ with sealed lid and leak prevention.\n- Medicine water tank: 2 m³ with thermal insulation.\n- Cooling water tank: 5 m³ connected to cooling pad system.\n- Diesel tank: 5 m³ (for diesel heaters).\n- All tanks equipped with electronic level indicators.",
                'sort_order' => 5,
            ],
            [
                'code' => 'CAGE_SPECS',
                'title_ar' => 'المواصفات العامة لأقفاص الدواجن',
                'title_en' => 'Cage Specifications',
                'category' => 'cages',
                'content_ar' => "- الأقفاص مصنعة من سلك مجلفن عالي الجودة (سماكة 2.5-3 مم).\n- الأقفاص مقسمة لأدوار (3 أو 4 أدوار حسب الطلب).\n- كل قفص يحتوي على: سقاية نبل، مجرى تغذية، صينية بيض (للبياض)، سلة روث.\n- الأقفاص مزودة بنظام تلقائي للتغذية والشرب.\n- الأبواب مصممة لتسهيل عملية الجمع والتفتيش.\n- الأقفاص مثبتة على هيكل معدني قوي يتحمل الأحمال الديناميكية.",
                'content_en' => "- Cages made from high-quality galvanized wire (2.5-3mm thickness).\n- Cages divided into tiers (3 or 4 tiers as requested).\n- Each cage contains: nipple drinker, feed trough, egg tray (layers), manure basket.\n- Automatic feeding and drinking system integrated.\n- Doors designed for easy collection and inspection.\n- Cages mounted on strong steel frame supporting dynamic loads.",
                'sort_order' => 6,
            ],
            [
                'code' => 'FEEDING_SYSTEM',
                'title_ar' => 'منظومة التغذية',
                'title_en' => 'Feeding System',
                'category' => 'feeding',
                'content_ar' => "عربة التغذية | Feed Trolley\n- يتم تركيب عربة تغذية أوتوماتيكية تتحرك على مجرى علوي.\n- العربة موزعة بالتساوي على طول صف الأقفاص.\n- مزودة بحساس مستوى العلف لمنع الفائض أو النقص.\n\nمجرى التغذية | Feed Trough\n- يتم تركيب مجرى تغذية أمام كل صف من الأقفاص.\n- المجرى مصنوع من الصاج المجلفن المعرج.\n- مزود بحواف منحنية لمنع هدر العلف.\n\nخزان العلف | Feed Storage\n- يتم تركيب خزان علف (سايلو) سعة 11 طن لكل عنبر.\n- السايلو مصنوع من الصاج المجلفن المعرج سمك 1.2 مم.\n- مزود بنظام قياس مستوى إلكتروني.",
                'content_en' => "Feed Trolley\n- Automatic feed trolley moving on overhead rail.\n- Evenly distributed along the cage row.\n- Equipped with feed level sensor to prevent overflow or shortage.\n\nFeed Trough\n- Feed trough installed in front of each cage row.\n- Made from galvanized corrugated sheet.\n- Curved edges to prevent feed waste.\n\nFeed Storage\n- Feed silo (11 tons capacity) per hall.\n- Made from galvanized corrugated sheet (1.2mm).\n- Electronic level measurement system.",
                'sort_order' => 7,
            ],
            [
                'code' => 'WATER_SUPPLY',
                'title_ar' => 'منظومة مياه الشرب',
                'title_en' => 'Water Supply System',
                'category' => 'water',
                'content_ar' => "- يتم تركيب نظام سقاية نبل أوتوماتيكي (Nipple Drinking System).\n- كل قفص مزود بعدد كافٍ من السقايات (10-12 نبل/قفص).\n- يتم تركيب خطوط مياه PVC عيار 25 مم مع منظم ضغط.\n- يتم تركيب نظام دواجن لكل دور على حدة.\n- يتم تركيب صمامات غسيل وفصل لكل صف.\n- يتم تركيب خزان مياه مع عوامة أوتوماتيكية.",
                'content_en' => "- Automatic nipple drinking system installed.\n- Each cage equipped with sufficient nipples (10-12 nipples/cage).\n- PVC water lines (25mm diameter) with pressure regulator.\n- Independent drinking system per tier.\n- Flush and isolation valves for each row.\n- Water tank with automatic float valve.",
                'sort_order' => 8,
            ],
            [
                'code' => 'MANURE_SYSTEM',
                'title_ar' => 'منظومة السبلة',
                'title_en' => 'Manure System',
                'category' => 'cleaning',
                'content_ar' => "أرضية السبلة | Manure Floor\n- يتم تركيب أرضية شبكية (سلك مجلفن) فوق حوض السبلة.\n- الشبيكة مصممة لتسمح بمرور الروث وتحمل وزن الطيور والعمال.\n\nآلية السحب | Mechanism\n- يتم تركيب حزام سحب روث (Manure Belt) من PVC مقاوم للأحماض.\n- الحزام يعمل أوتوماتيكياً بنظام تروس ومحركات.\n- يتم جمع الروث تلقائياً في نقطة محددة لسهولة التخلص.\n- يتم تركيب نظام تجفيف هوائي للروث لتقليل الرطوبة.",
                'content_en' => "Manure Floor\n- Wire mesh floor (galvanized) installed above manure pit.\n- Mesh designed to allow manure passage while supporting bird and worker weight.\n\nMechanism\n- PVC manure belt (acid-resistant) installed.\n- Belt operates automatically with gear and motor system.\n- Manure collected automatically at designated point.\n- Air drying system reduces manure moisture.",
                'sort_order' => 9,
            ],
            [
                'code' => 'FEED_STORAGE',
                'title_ar' => 'خزان العلف',
                'title_en' => 'Feed Storage',
                'category' => 'feeding',
                'content_ar' => "- يتم توريد وتركيب خزان علف مركزي (سايلو) سعة 11 طن.\n- السايلو مصنوع من الصاج المجلفن المعرج سمك 1.2 مم.\n- يتم تركيب سير نقل العلف (Screw Feeder) من السايلو إلى العنبر.\n- السير مصنوع من الستانلس ستيل مقاوم للصدأ.\n- مزود بمحرك كهربائي 1.5 حصان مع علبة تروس.\n- يتم تركيب حساس مستوى إلكتروني في السايلو.",
                'content_en' => "- Central feed silo (11 tons capacity).\n- Made from galvanized corrugated sheet (1.2mm).\n- Screw feeder installed from silo to hall.\n- Feeder made from stainless steel (rust-resistant).\n- 1.5 HP electric motor with gearbox.\n- Electronic level sensor installed in silo.",
                'sort_order' => 10,
            ],
            [
                'code' => 'VENTILATION',
                'title_ar' => 'التهوية',
                'title_en' => 'Ventilation',
                'category' => 'ventilation',
                'content_ar' => "شبابيك التهوية | Air Inlet\n- يتم تركيب شبابيك تهوية (Air Inlet) على جانبي العنبر.\n- الشبابيك مصنوعة من البلاستيك المقاوم للأشعة فوق البنفسجية.\n- مزودة بألواح موجهة للهواء (Baffle) لتوزيع الهواء بالتساوي.\n- يتم التحكم في فتح/إغلاق الشبابيك أوتوماتيكياً حسب درجة الحرارة.\n\nخلايا التبريد | Cooling Pads\n- يتم تركيب خلايا تبريد (ورق الوادي) على جانب العنبر.\n- سمك الخلايا 15 سم لأقصى كفاءة تبريد.\n- مزودة بنظام ضخ مياه أوتوماتيكي.\n- يتم تركيب شبكة حماية أمام الخلايا.",
                'content_en' => "Air Inlet\n- Air inlet windows installed on both sides of the hall.\n- Windows made from UV-resistant plastic.\n- Equipped with baffle plates for even air distribution.\n- Automatic open/close control based on temperature.\n\nCooling Pads\n- Cooling pads (cellulose) installed on hall side.\n- 15cm thickness for maximum cooling efficiency.\n- Automatic water pumping system.\n- Protective mesh in front of pads.",
                'sort_order' => 11,
            ],
            [
                'code' => 'HEATING',
                'title_ar' => 'التدفئة',
                'title_en' => 'Heating',
                'category' => 'cooling',
                'content_ar' => "شفاطات العادم | Exhaust Fans\n- يتم تركيب شفاطات رئيسية MUNTER ITALY EM50 (140×140 سم).\n- يتم تركيب شفاطات جانبية MUNTER ITALY EM36 (100×100 سم).\n- الشفاطات مزودة بأبواب ذاتية الإغلاق لمنع تيار هواء عكسي.\n- يتم التحكم في سرعة الشفاطات أوتوماتيكياً.\n\nالدفايات | Heaters\n- يتم تركيب دفايات ممدوح خليفة (130 م³/ساعة).\n- قلب الدفاية من الستانلس ستيل.\n- مزودة بشعلة إشعال أوتوماتيكية وحساس حرارة.\n- يتم توزيع الدفايات بالتساوي داخل العنبر.",
                'content_en' => "Exhaust Fans\n- Main exhaust fans MUNTER ITALY EM50 (140×140 cm).\n- Side exhaust fans MUNTER ITALY EM36 (100×100 cm).\n- Fans equipped with self-closing shutters to prevent backdraft.\n- Automatic speed control.\n\nHeaters\n- Mamdouh Khalifa heaters (130 m³/hour).\n- Stainless steel heater core.\n- Automatic ignition flame and temperature sensor.\n- Evenly distributed inside the hall.",
                'sort_order' => 12,
            ],
            [
                'code' => 'ELECTRIC_PANELS',
                'title_ar' => 'لوحات الكهرباء',
                'title_en' => 'Electric Panels',
                'category' => 'electrical',
                'content_ar' => "- يتم تركيب لوحة كنترول رئيسية تشمل كل وسائل الحماية.\n- الكابلات نحاسية معتمدة من الجهات الرسمية (Nexans أو Egyflex).\n- إنارة LED موفرة للطاقة موزعة بالتساوي داخل العنبر.\n- تأريض كهربائي كامل لجميع الأجزاء المعدنية.\n- يتم تركيب لوحة تحكم أوتوماتيكية (MUNTER TRIO 20) للتحكم في:\n  • درجة الحرارة والرطوبة\n  • نظام التهوية والتبريد\n  • نظام التغذية والشرب\n  • الإنارة والتدفئة",
                'content_en' => "- Main control panel with all protection devices.\n- Copper cables approved by official authorities (Nexans or Egyflex).\n- Energy-saving LED lighting evenly distributed.\n- Complete electrical grounding for all metal parts.\n- Automatic control panel (MUNTER TRIO 20) controls:\n  • Temperature and humidity\n  • Ventilation and cooling system\n  • Feeding and drinking system\n  • Lighting and heating",
                'sort_order' => 13,
            ],
            [
                'code' => 'BATTERY_SPECS',
                'title_ar' => 'المواصفات الفنية للبطاريات',
                'title_en' => 'Battery Specifications',
                'category' => 'cages',
                'content_ar' => "- البطاريات مصنعة من سلك حديدي مجلفن (Hot Dip Galvanized) سماكة 2.5-3 مم.\n- كل بطارية تحتوي على عدد من الأقفاص حسب النوع:\n  • تسمين 4 أدوار: 2,304 قفص، 41,472 طائر\n  • تسمين 3 أدوار: 1,728 قفص، 27,648 طائر\n  • بياض H-Type: 2,400 قفص، 24,000 طائر\n- أبعاد القفص الواحد: 60×70×42 سم (طول × عرض × ارتفاع).\n- السعة: 18 طائر/قفص (تسمين) أو 10 طيور/قفص (بياض).\n- الأقفاص مزودة بنظام آلي للتغذية والشرب والتجميع.\n- ضمان الصاج والسلك: 12 سنة ضد الصدأ.\n- ضمان عيوب التصنيع: 12 شهراً.",
                'content_en' => "- Batteries made from hot-dip galvanized iron wire (2.5-3mm).\n- Each battery contains a number of cages depending on type:\n  • Broiler 4 tiers: 2,304 cages, 41,472 birds\n  • Broiler 3 tiers: 1,728 cages, 27,648 birds\n  • Layer H-Type: 2,400 cages, 24,000 birds\n- Single cage dimensions: 60×70×42 cm (L×W×H).\n- Capacity: 18 birds/cage (broiler) or 10 birds/cage (layer).\n- Automatic feeding, drinking, and collection system.\n- Wire warranty: 12 years against rust.\n- Manufacturing defects warranty: 12 months.",
                'sort_order' => 14,
            ],
            [
                'code' => 'DISINFECTION_GUIDE',
                'title_ar' => 'دليل التطهير',
                'title_en' => 'Disinfection Guide',
                'category' => 'cleaning',
                'content_ar' => "خطوات التطهير الموصى بها:\n\n1. التنظيف الجاف:\n   - إزالة كل الروث والغبار من الأقفاص والأرضية.\n   - استخدام المكنسة والمظلة لإزالة الغبار العالق.\n\n2. الغسيل بالماء:\n   - غسيل جميع الأسطح بالماء تحت ضغط عالٍ.\n   - التأكد من إزالة كل الرواسب العضوية.\n\n3. التطهير الكيميائي:\n   - رش المحاليل المطهرة (فورمالدهيد، فنيكول) على جميع الأسطح.\n   - ترك المحلول لمدة 24 ساعة على الأقل.\n\n4. التعقيم بالحرارة:\n   - رفع درجة حرارة العنبر إلى 60°C لمدة 24 ساعة (إن أمكن).\n\n5. الفترة الصحية:\n   - ترك العنبر فارغاً لمدة 7-14 يوماً قبل استقبال الدفعة الجديدة.\n\n6. الفحص البكتيري:\n   - أخذ مسحات من الأسطح للتأكد من خلوها من البكتيريا الضارة.",
                'content_en' => "Recommended disinfection steps:\n\n1. Dry Cleaning:\n   - Remove all manure and dust from cages and floor.\n   - Use broom and shovel to remove adhered dust.\n\n2. Water Washing:\n   - Wash all surfaces with high-pressure water.\n   - Ensure removal of all organic residues.\n\n3. Chemical Disinfection:\n   - Spray disinfectant solutions (formaldehyde, phenolic) on all surfaces.\n   - Leave solution for at least 24 hours.\n\n4. Heat Sterilization:\n   - Raise hall temperature to 60°C for 24 hours (if possible).\n\n5. Rest Period:\n   - Leave hall empty for 7-14 days before receiving new batch.\n\n6. Bacterial Testing:\n   - Take surface swabs to ensure absence of harmful bacteria.",
                'sort_order' => 15,
            ],
        ];

        foreach ($sections as $section) {
            QuotationSection::updateOrCreate(
                ['code' => $section['code']],
                $section + ['is_active' => true, 'applicable_quotation_types' => []]
            );
        }
    }

    protected function seedQuotationTerms(): void
    {
        $terms = [
            [
                'code' => 'WARRANTY',
                'title_ar' => 'بند الضمان',
                'title_en' => 'Warranty Terms',
                'content_ar' => "1) يضمن البائع كل المنتجات والمعدات الموردة ضد عيوب التصنيع لمدة ({{warranty_months}}) شهراً من تاريخ التركيب والتشغيل.\n\n2) قيمة الأجزاء المستبدلة خلال فترة الضمان تتحملها شركة MI-Metal Industries بالكامل.\n\n3) يضمن البائع الصاج والسلك المجلفن ضد الصدأ والتآكل لمدة ({{steel_warranty_years}}) سنة من تاريخ التصنيع.\n\n4) لا يشمل الضمان الأعطال الناتجة عن سوء الاستخدام أو التعديلات غير المصرح بها.\n\n5) يجب إبلاغ البائع بأي عطل خلال 48 ساعة من اكتشافه.\n\n6) يحتفظ البائع بالحق في إصلاح أو استبدال المنتج المعيب حسب تقديره.",
                'content_en' => "1) The seller guarantees all supplied products and equipment against manufacturing defects for ({{warranty_months}}) months from installation date.\n\n2) Replacement parts during warranty period are fully covered by MI-Metal Industries.\n\n3) Galvanized sheet and wire guaranteed against rust and corrosion for ({{steel_warranty_years}}) years from manufacturing date.\n\n4) Warranty does not cover faults resulting from misuse or unauthorized modifications.\n\n5) Seller must be notified of any defect within 48 hours of discovery.\n\n6) Seller reserves the right to repair or replace defective product at their discretion.",
                'variables' => [
                    ['name' => 'warranty_months', 'label' => 'شهور الضمان', 'type' => 'number', 'default' => '12'],
                    ['name' => 'steel_warranty_years', 'label' => 'سنوات ضمان الصاج', 'type' => 'number', 'default' => '12'],
                ],
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'INSTALLATION',
                'title_ar' => 'بند التركيب',
                'title_en' => 'Installation Terms',
                'content_ar' => "1) سعر البطاريات والمشتملات شامل التركيب والتشغيل التجريبي داخل العنبر.\n\n2) {{#includes_transport}}يشمل السعر نقل المعدات من المصنع إلى موقع المشروع.{{/includes_transport}}\n\n3) يتحمل العميل توفير: مياه شرب نظيفة، أماكن إقامة للعمال، مصدر طهي، مبرد مياه، مساحة عمل آمنة.\n\n4) مدة التركيب: 7-14 يوم عمل حسب حجم المشروع.\n\n5) يتم تقديم تدريب عملي للعمالة على التشغيل والصيانة لمدة 3 أيام.",
                'content_en' => "1) Cages and accessories price includes installation and trial operation inside the hall.\n\n2) {{#includes_transport}}Price includes equipment transportation from factory to project site.{{/includes_transport}}\n\n3) Client shall provide: clean drinking water, worker accommodation, cooking source, water cooler, safe working area.\n\n4) Installation period: 7-14 working days depending on project size.\n\n5) Practical training for workers on operation and maintenance for 3 days.",
                'variables' => [
                    ['name' => 'includes_transport', 'label' => 'يشمل النقل', 'type' => 'boolean', 'default' => 'true'],
                ],
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'CONTRACT',
                'title_ar' => 'بند التعاقد',
                'title_en' => 'Contract Terms',
                'content_ar' => "يتم سداد المبلغ الإجمالي على دفعات:\n- الدفعة الأولى ({{payment_1_percentage}}%): عند توقيع العقد.\n- الدفعة الثانية ({{payment_2_percentage}}%): عند بدء التركيبات.\n{{#payment_3_percentage}}- الدفعة الثالثة ({{payment_3_percentage}}%): بعد التسليم النهائي واعتماد المحضر.{{/payment_3_percentage}}\n\nملاحظات:\n- جميع الدفعات تُسدد بشيك مصدق أو تحويل بنكي.\n- لا يتم بدء التصنيع إلا بعد استلام الدفعة الأولى.\n- يحق للبائع تعليق العمل في حالة تأخر أي دفعة عن موعدها بأكثر من 7 أيام.",
                'content_en' => "Total amount is paid in installments:\n- First payment ({{payment_1_percentage}}%): Upon contract signing.\n- Second payment ({{payment_2_percentage}}%): Upon installation start.\n{{#payment_3_percentage}}- Third payment ({{payment_3_percentage}}%): After final delivery and approval.{{/payment_3_percentage}}\n\nNotes:\n- All payments by certified check or bank transfer.\n- Manufacturing starts only after receiving first payment.\n- Seller reserves the right to suspend work if any payment is delayed by more than 7 days.",
                'variables' => [
                    ['name' => 'payment_1_percentage', 'label' => 'نسبة الدفعة الأولى', 'type' => 'number', 'default' => '70'],
                    ['name' => 'payment_2_percentage', 'label' => 'نسبة الدفعة الثانية', 'type' => 'number', 'default' => '25'],
                    ['name' => 'payment_3_percentage', 'label' => 'نسبة الدفعة الثالثة', 'type' => 'number', 'default' => '5'],
                ],
                'is_required' => true,
                'is_default' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'SPARE_PARTS',
                'title_ar' => 'بند قطع الغيار',
                'title_en' => 'Spare Parts Terms',
                'content_ar' => "1) تلتزم شركة MI-Metal Industries بضمان توفير قطع الغيار الأصلية لمدة ({{spare_parts_years}}) سنة من تاريخ التصنيع.\n\n2) يتم توريد قطع الغيار بأسعار التكلفة خلال فترة الضمان، وبأسعار السوق بعد انتهائها.\n\n3) يتم shipping قطع الغيار خلال 14 يوم عمل من تاريخ الطلب.\n\n4) يحق للعميل شراء قطع غيار احتياطية عند التعاقد بخصم 10%.",
                'content_en' => "1) MI-Metal Industries commits to providing original spare parts for ({{spare_parts_years}}) years from manufacturing date.\n\n2) Spare parts supplied at cost price during warranty period, and market price after expiration.\n\n3) Spare parts shipped within 14 working days from order date.\n\n4) Client may purchase spare parts at contract signing with 10% discount.",
                'variables' => [
                    ['name' => 'spare_parts_years', 'label' => 'سنوات توفير قطع الغيار', 'type' => 'number', 'default' => '12'],
                ],
                'is_required' => false,
                'is_default' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($terms as $term) {
            QuotationTerm::updateOrCreate(
                ['code' => $term['code']],
                $term + ['is_active' => true]
            );
        }
    }

    protected function linkSectionImages(): void
    {
        // ربط الأقسام بصور placeholder من المكتبة
        $links = [
            'STEEL_WORK' => ['STEEL_FRAME_01', 'STEEL_ROOF_01', 'STEEL_WALL_01', 'STEEL_INSULATION_01'],
            'CIVIL_WORK' => ['CIVIL_FOUNDATION_01', 'CIVIL_FLOOR_01', 'CIVIL_BRICK_01'],
            'SERVICE_DOORS' => ['CIVIL_DOOR_01'],
            'COOLING_SHADES' => ['COOLING_SHADE_01', 'COOLING_SYSTEM_01'],
            'WATER_TANKS' => ['WATER_TANK_01'],
            'CAGE_SPECS' => ['CAGE_BROILER_01', 'CAGE_BROILER_02', 'CAGE_LAYER_01'],
            'FEEDING_SYSTEM' => ['FEED_TROLLEY_01', 'FEED_TROUGH_01', 'FEED_SILO_01', 'FEED_SCREW_01'],
            'WATER_SUPPLY' => ['WATER_NIPPLE_01', 'WATER_REGULATOR_01'],
            'MANURE_SYSTEM' => ['CLEAN_MANURE_01', 'CLEAN_SCRAPER_01'],
            'FEED_STORAGE' => ['FEED_SILO_01', 'FEED_SCREW_01'],
            'VENTILATION' => ['VENT_FAN_MAIN_01', 'VENT_FAN_SIDE_01', 'VENT_INLET_01', 'COOLING_PADS_01'],
            'HEATING' => ['VENT_FAN_MAIN_01', 'VENT_FAN_SIDE_01'],
            'ELECTRIC_PANELS' => ['ELEC_PANEL_01', 'ELEC_CONTROLLER_01', 'ELEC_CABLE_01', 'ELEC_LED_01'],
            'BATTERY_SPECS' => ['CAGE_BATTERY_01', 'CAGE_BROILER_01'],
            'DISINFECTION_GUIDE' => ['CLEAN_DISINFECTION_01'],
        ];

        foreach ($links as $sectionCode => $imageCodes) {
            $section = QuotationSection::where('code', $sectionCode)->first();
            if (! $section) {
                continue;
            }

            $imageIds = ImageLibrary::whereIn('code', $imageCodes)->pluck('id')->toArray();
            if (! empty($imageIds)) {
                $section->update(['default_images' => $imageIds]);
            }
        }
    }
}
