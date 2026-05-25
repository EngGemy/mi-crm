<x-filament-panels::page>
<div dir="rtl" x-data="{ tab: 'formulas' }" class="space-y-6">

    {{-- ترويسة --}}
    <div class="rounded-2xl bg-gradient-to-l from-primary-600 to-primary-800 p-6 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <x-heroicon-o-academic-cap class="w-12 h-12 opacity-80" />
            <div>
                <h1 class="text-2xl font-bold">دليل التدريب — مندوب المبيعات</h1>
                <p class="mt-1 text-primary-100">كل ما تحتاجه لإتمام صفقة بطاريات الدواجن — المعادلات والتسعير وخطوات العمل</p>
            </div>
        </div>
    </div>

    {{-- التابات --}}
    <div class="flex gap-2 flex-wrap border-b border-gray-200 dark:border-gray-700 pb-0">
        @foreach ([
            'formulas' => ['label' => 'المعادلات الفنية', 'icon' => 'heroicon-o-calculator'],
            'pricing'  => ['label' => 'التسعير والضريبة', 'icon' => 'heroicon-o-currency-dollar'],
            'workflow' => ['label' => 'خطوات العمل', 'icon' => 'heroicon-o-arrow-path'],
            'faq'      => ['label' => 'أسئلة شائعة', 'icon' => 'heroicon-o-question-mark-circle'],
            'quiz'     => ['label' => 'اختبر نفسك', 'icon' => 'heroicon-o-clipboard-document-check'],
        ] as $key => $meta)
        <button
            @click="tab = '{{ $key }}'"
            :class="tab === '{{ $key }}'
                ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            class="flex items-center gap-1.5 px-4 py-2.5 text-sm transition-colors -mb-px"
        >
            <x-dynamic-component :component="$meta['icon']" class="w-4 h-4" />
            {{ $meta['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ===== TAB 1: المعادلات الفنية ===== --}}
    <div x-show="tab === 'formulas'" x-cloak>

        <div class="mb-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>المثال المرجعي المستخدم في كل الحسابات:</strong>
                عنبر طوله <strong>{{ $example['barn_length'] }}م</strong> × عرض <strong>{{ $example['hall_width'] }}م</strong>،
                منطقة خدمات <strong>{{ $example['service_length'] }}م</strong>،
                <strong>{{ $example['tiers'] }} أدوار</strong>،
                وزن الطائر <strong>{{ $example['bird_weight'] }} كجم</strong>.
                القيم مستخرجة من الإعدادات الفعلية للحاسبة.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            @php
                $formulas = [
                    [
                        'num' => '١',
                        'title' => 'الطول الفعّال',
                        'formula' => 'الطول الفعّال = طول العنبر − منطقة الخدمات',
                        'example' => "{$example['barn_length']} − {$example['service_length']} = {$example['effective_length']} م",
                        'note' => 'منطقة الخدمات تُقرأ من الإعداد: poultry_pricing.default_service_length (افتراضي: '.$example['service_length'].' م)',
                        'color' => 'blue',
                    ],
                    [
                        'num' => '٢',
                        'title' => 'عدد الخطوط من العرض',
                        'formula' => "12م → 4 خطوط | 15م → 5 خطوط | 16.5م → 6 خطوط",
                        'example' => "عرض {$example['hall_width']}م → {$example['lines']} خطوط",
                        'note' => 'الخريطة مخزّنة في الإعداد width_lines_map، أو يمكن إدخال عدد الخطوط مباشرة',
                        'color' => 'purple',
                    ],
                    [
                        'num' => '٣',
                        'title' => 'عدد الطيور/العش (تسمين)',
                        'formula' => "1.6 كجم → 21 | 1.85 → 18 | 2.1 → 16 | 2.65 → 13 | 2.8 → 12",
                        'example' => "وزن {$example['bird_weight']} كجم → {$example['birds_per_nest']} طيور/عش",
                        'note' => 'الخريطة من الإعداد broiler_weight_birds_map — أوزان مختلفة تعطي كثافات مختلفة',
                        'color' => 'green',
                    ],
                    [
                        'num' => '٤',
                        'title' => 'عدد الأعشاش/الخط',
                        'formula' => 'أعشاش/الخط = الطول الفعّال × 2 (وجهان) × عدد الأدوار',
                        'example' => "{$example['effective_length']} × 2 × {$example['tiers']} = {$example['nests_per_line']} عش/خط",
                        'note' => 'الوجهان: الأعشاش تُركَّب على جانبَي الخط (وجه أمامي + خلفي)',
                        'color' => 'orange',
                    ],
                    [
                        'num' => '٥',
                        'title' => 'إجمالي الأعشاش',
                        'formula' => 'إجمالي الأعشاش = أعشاش/الخط × عدد الخطوط',
                        'example' => "{$example['nests_per_line']} × {$example['lines']} = ".number_format($example['total_nests'])." عش",
                        'note' => 'هذا الرقم يحدد سعة العنبر الكلية قبل احتساب كثافة الطيور',
                        'color' => 'teal',
                    ],
                    [
                        'num' => '٦',
                        'title' => 'إجمالي الطيور (السعة)',
                        'formula' => 'إجمالي الطيور = إجمالي الأعشاش × عدد الطيور/العش',
                        'example' => number_format($example['total_nests'])." × {$example['birds_per_nest']} = ".number_format($example['total_birds'])." طيرة",
                        'note' => 'هذا هو الرقم الأساسي الذي يُبنى عليه تسعير البطاريات والشفاطات',
                        'color' => 'red',
                    ],
                    [
                        'num' => '٧',
                        'title' => 'عدد الشفاطات الرئيسية',
                        'formula' => 'الشفاطات = تقريب لأعلى ( إجمالي الطيور × أقصى وزن للتهوية ÷ سعة الشفاطة )',
                        'example' => $example['fan_formula'],
                        'note' => "سعة الشفاطة = {$example['fan_capacity']} كجم (من الإعداد fan_capacity_kg). الحمل = ".number_format($example['fan_load_kg'])." كجم",
                        'color' => 'indigo',
                    ],
                    [
                        'num' => '٨',
                        'title' => 'طول وحدات التبريد',
                        'formula' => 'التبريد (م) = تقريب لأعلى ( عدد الشفاطات × '.number_format($example['cooling_per_fan'], 1).' )',
                        'example' => "ceil({$example['main_fans']} × {$example['cooling_per_fan']}) = {$example['cooling_pad']} م",
                        'note' => "المعامل ".number_format($example['cooling_per_fan'], 1)." م/شفاطة مأخوذ من الإعداد cooling_pad_meters_per_fan",
                        'color' => 'cyan',
                    ],
                    [
                        'num' => '٩',
                        'title' => 'شبابيك الهواء (Inlets) — تسمين',
                        'formula' => "إذا طول العنبر فردي: (الطول − 3) ÷ 2\nإذا طول العنبر زوجي: (الطول − 4) ÷ 2",
                        'example' => "{$example['barn_length']}م (زوجي) → ({$example['barn_length']} − 4) ÷ 2 = {$example['air_windows']} شباك",
                        'note' => 'الشبابيك تُوضع على الجانبين لضمان تهوية طولية متوازنة داخل العنبر',
                        'color' => 'lime',
                    ],
                    [
                        'num' => '١٠',
                        'title' => 'أعشاش البياض (Layer)',
                        'formula' => 'أعشاش وجه واحد = الطول الفعّال ÷ وحدة العش (0.60م افتراضي)',
                        'example' => "70 ÷ 0.60 = 116 عش/وجه → أعشاش/خط = 116 × 2 وجه × أدوار",
                        'note' => 'البياض يستخدم وحدة طولية ثابتة بدلاً من خريطة الوزن. وحدة العش من الإعداد layer_nest_module_m',
                        'color' => 'amber',
                    ],
                ];
            @endphp

            @foreach ($formulas as $f)
            <div class="rounded-xl border border-{{ $f['color'] }}-200 dark:border-{{ $f['color'] }}-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="bg-{{ $f['color'] }}-50 dark:bg-{{ $f['color'] }}-950/30 px-4 py-3 flex items-center gap-3">
                    <span class="text-2xl font-bold text-{{ $f['color'] }}-600 dark:text-{{ $f['color'] }}-400">{{ $f['num'] }}</span>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $f['title'] }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 font-mono text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $f['formula'] }}</div>
                    <div class="rounded-lg bg-{{ $f['color'] }}-50 dark:bg-{{ $f['color'] }}-950/20 border border-{{ $f['color'] }}-100 dark:border-{{ $f['color'] }}-900 p-3">
                        <p class="text-xs text-{{ $f['color'] }}-600 dark:text-{{ $f['color'] }}-400 font-medium mb-1">مثال محلول:</p>
                        <p class="font-semibold text-{{ $f['color'] }}-700 dark:text-{{ $f['color'] }}-300">{{ $f['example'] }}</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-start gap-1.5">
                        <x-heroicon-o-light-bulb class="w-4 h-4 shrink-0 text-yellow-500 mt-0.5" />
                        {{ $f['note'] }}
                    </p>
                </div>
            </div>
            @endforeach

        </div>
    </div>

    {{-- ===== TAB 2: التسعير والضريبة ===== --}}
    <div x-show="tab === 'pricing'" x-cloak>

        <div class="space-y-6">

            {{-- شرح بنود التسعير --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="bg-emerald-50 dark:bg-emerald-950/30 px-4 py-3">
                    <h3 class="font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                        <x-heroicon-o-banknotes class="w-5 h-5" />
                        بنود التسعير — كيف تُحسب القيمة؟
                    </h3>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-3 py-2 text-right font-semibold">البند</th>
                                    <th class="px-3 py-2 text-right font-semibold">الوحدة</th>
                                    <th class="px-3 py-2 text-right font-semibold">الكمية</th>
                                    <th class="px-3 py-2 text-right font-semibold">سعر الوحدة</th>
                                    <th class="px-3 py-2 text-right font-semibold">الإجمالي</th>
                                    <th class="px-3 py-2 text-right font-semibold text-xs text-gray-500">المعادلة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @php
                                    $rows = [
                                        ['البطاريات (التسمين)', 'مقطوعة', '1', number_format($pricingParams['price_per_bird']).'×'.number_format($example['total_birds']).' طيرة', number_format($example['battery_total']), 'عدد الطيور × سعر الطائر (من الإعداد price_per_bird)'],
                                        ['الخرسانات', 'م²', number_format($example['concrete_area']), number_format($pricingParams['concrete_cost_per_m2']), number_format($example['concrete_total']), 'الطول × العرض × سعر/م²'],
                                        ['الجملون/الاستيل', 'م²', number_format($example['steel_area']), number_format($pricingParams['steel_cost_per_m2']), number_format($example['steel_total']), 'الطول × العرض × سعر/م²'],
                                        ['الحوائط', 'م²', number_format($example['walls_area']), number_format($pricingParams['wall_cost_per_m2']), number_format($example['walls_total']), 'الطول × الارتفاع × 2 × سعر/م²'],
                                        ['الشفاطات الرئيسية', 'قطعة', $example['main_fans'], number_format($pricingParams['back_fan_unit_price']), number_format($example['fans_total']), 'عدد الشفاطات × سعر القطعة'],
                                        ['وحدات التبريد', 'م', $example['cooling_pad'], number_format($pricingParams['cooling_unit_price']), number_format($example['cooling_total']), 'طول التبريد × سعر/م'],
                                        ['الشبابيك (Inlets)', 'قطعة', $example['air_windows'], number_format($pricingParams['window_unit_price']), number_format($example['windows_total']), 'عدد الشبابيك × سعر القطعة'],
                                        ['لوحة مونيتر', 'مقطوعة', '1', number_format($pricingParams['control_fixed_cost']), number_format($example['control_total']), 'تكلفة ثابتة من الإعداد'],
                                        ['الخزانات', 'مقطوعة', '1', number_format($pricingParams['tanks_fixed_cost']), number_format($example['tanks_total']), 'تكلفة ثابتة من الإعداد'],
                                    ];
                                @endphp
                                @foreach ($rows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2 font-medium">{{ $row[0] }}</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $row[1] }}</td>
                                    <td class="px-3 py-2">{{ $row[2] }}</td>
                                    <td class="px-3 py-2 font-mono">{{ $row[3] }}</td>
                                    <td class="px-3 py-2 font-bold text-emerald-700 dark:text-emerald-400 font-mono">{{ $row[4] }}</td>
                                    <td class="px-3 py-2 text-xs text-gray-400">{{ $row[5] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- إجمالي وضريبة --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5">
                    <h4 class="font-semibold mb-4 text-gray-700 dark:text-gray-300">الملخص المالي (مثال)</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">المجموع قبل الضريبة</span>
                            <span class="font-mono font-semibold">{{ number_format($example['subtotal']) }} ج.م</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ضريبة القيمة المضافة ({{ number_format($example['vat_rate'], 0) }}٪)</span>
                            <span class="font-mono text-orange-600">{{ number_format($example['vat_amount']) }} ج.م</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between">
                            <span class="font-bold text-lg">الإجمالي</span>
                            <span class="font-mono font-bold text-lg text-primary-600">{{ number_format($example['total']) }} ج.م</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-5 space-y-3">
                    <h4 class="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                        <x-heroicon-o-eye-slash class="w-5 h-5" />
                        قاعدة إخفاء سعر الطائر للوحدة
                    </h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        بند البطاريات يظهر في عرض السعر كـ <strong>«مقطوعة»</strong> (Lot) بدون إظهار سعر الطائر الواحد.
                        لذلك المبلغ الإجمالي = عدد الطيور × سعر الطائر يُحسب داخل النظام ويُعرض كمبلغ إجمالي فقط.
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                        السبب: سعر الطائر معلومة تجارية حساسة. العميل يرى المبلغ الكلي للبطاريات فقط.
                    </p>
                </div>
            </div>

            {{-- الضريبة --}}
            <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20 p-5">
                <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-3">ضريبة القيمة المضافة (VAT)</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">14٪</p>
                        <p class="text-blue-600">مصر</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">15٪</p>
                        <p class="text-blue-600">المملكة العربية السعودية</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">0٪</p>
                        <p class="text-blue-600">معفاة</p>
                    </div>
                </div>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-3">النسبة الحالية المطبّقة: {{ number_format($vatRate, 0) }}٪ — تُقرأ من الإعداد tax.vat_rate_egypt/ksa</p>
            </div>

        </div>
    </div>

    {{-- ===== TAB 3: خطوات العمل ===== --}}
    <div x-show="tab === 'workflow'" x-cloak>
        <div class="space-y-4">

            @php
                $steps = [
                    [
                        'num' => '1',
                        'icon' => 'heroicon-o-user-plus',
                        'color' => 'violet',
                        'title' => 'إنشاء فرصة بيع (Lead)',
                        'desc' => 'من قائمة «العملاء المحتملون» → جديد. أدخِل: الاسم، الشركة، رقم الواتساب، المصدر (معرض/إحالة/إعلان)، القيمة المتوقّعة، المندوب المسؤول.',
                        'tip' => 'كلّما كانت بيانات الـ Lead مكتملة، كلّما ساعدك النظام في التتبع والتنبيهات.',
                    ],
                    [
                        'num' => '2',
                        'icon' => 'heroicon-o-calculator',
                        'color' => 'blue',
                        'title' => 'إدخال بيانات العنبر في الحاسبة',
                        'desc' => 'من «عروض الدواجن» → جديد. أدخِل: طول/عرض/ارتفاع العنبر، عدد الأدوار، وزن الطائر المستهدف. النظام يحسب الكميات تلقائيًا.',
                        'tip' => 'تأكد من أن طول العنبر أكبر من منطقة الخدمات (10م افتراضي). إذا لم يكن هناك خدمات اجعلها 0.',
                    ],
                    [
                        'num' => '3',
                        'icon' => 'heroicon-o-document-text',
                        'color' => 'emerald',
                        'title' => 'توليد عرض السعر',
                        'desc' => 'بعد إدخال البيانات، اضغط «حساب وحفظ». النظام يولّد البنود بأسعار تلقائية. راجع البنود وعدّل أي قيم إذا لزم، ثم احفظ العرض.',
                        'tip' => 'يمكنك تعديل الكميات أو الأسعار يدويًا بعد الحساب التلقائي.',
                    ],
                    [
                        'num' => '4',
                        'icon' => 'heroicon-o-share',
                        'color' => 'orange',
                        'title' => 'مشاركة العرض مع العميل',
                        'desc' => 'من صفحة العرض: «تنزيل PDF» لإرساله بالبريد أو واتساب، أو «صورة» لإرساله مباشرة عبر المحادثة، أو اضغط «إرسال» للتسجيل في النظام.',
                        'tip' => 'بعد الإرسال يتحوّل حالة العرض إلى «مُرسَل» ويُسجَّل في سجل التدقيق تلقائيًا.',
                    ],
                    [
                        'num' => '5',
                        'icon' => 'heroicon-o-check-badge',
                        'color' => 'teal',
                        'title' => 'اعتماد العميل للعرض',
                        'desc' => 'عند موافقة العميل، اضغط «اعتماد» في صفحة العرض. يتحوّل الحالة إلى «معتمد». الآن يمكنك تحويله لعقد.',
                        'tip' => 'الاعتماد لا يتطلب توقيعًا ورقيًا — هو مجرد تسجيل موافقة العميل في النظام.',
                    ],
                    [
                        'num' => '6',
                        'icon' => 'heroicon-o-document-check',
                        'color' => 'green',
                        'title' => 'تحويل العرض إلى عقد',
                        'desc' => 'من صفحة العرض المعتمد: اضغط «تحويل لعقد». النظام ينشئ عقدًا بكل بنود العرض تلقائيًا. راجع بنود العقد وأضِف الشروط والبنود القانونية.',
                        'tip' => 'التحويل يُسجَّل تلقائيًا في سجل التدقيق كحدث «تحويل».',
                    ],
                    [
                        'num' => '7',
                        'icon' => 'heroicon-o-banknotes',
                        'color' => 'yellow',
                        'title' => 'جدول الدفعات والمتابعة',
                        'desc' => 'من صفحة العقد، حدد جدول الدفعات (دفعة مقدمة، دفعات مرحلية، دفعة نهائية). النظام يتابع الدفعات المستحقة ويُنبّهك عند الاستحقاق.',
                        'tip' => 'يمكن المحاسب تسجيل الدفعات من قائمة «الدفعات» مباشرة.',
                    ],
                    [
                        'num' => '8',
                        'icon' => 'heroicon-o-arrow-path',
                        'color' => 'indigo',
                        'title' => 'المتابعة الدورية',
                        'desc' => 'راجع صفحة «العملاء المحتملون» يوميًا. العملاء الحمراء (بدون تواصل منذ فترة) يحتاجون متابعة فورية. استخدم أزرار الواتساب والاتصال السريع.',
                        'tip' => 'النظام يُنبّهك تلقائيًا للعملاء المحتملين الذين لم يُتواصَل معهم منذ فترة.',
                    ],
                ];
            @endphp

            @foreach ($steps as $step)
            <div class="flex gap-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="shrink-0 w-10 h-10 rounded-full bg-{{ $step['color'] }}-100 dark:bg-{{ $step['color'] }}-900/30 flex items-center justify-center">
                    <span class="font-bold text-{{ $step['color'] }}-700 dark:text-{{ $step['color'] }}-300">{{ $step['num'] }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <x-dynamic-component :component="$step['icon']" class="w-5 h-5 text-{{ $step['color'] }}-600" />
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100">{{ $step['title'] }}</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $step['desc'] }}</p>
                    <div class="flex items-start gap-1.5 text-xs text-{{ $step['color'] }}-700 dark:text-{{ $step['color'] }}-400 bg-{{ $step['color'] }}-50 dark:bg-{{ $step['color'] }}-950/20 rounded-lg px-3 py-2">
                        <x-heroicon-o-light-bulb class="w-4 h-4 shrink-0 mt-0.5" />
                        {{ $step['tip'] }}
                    </div>
                </div>
            </div>
            @endforeach

            {{-- الأخطاء الشائعة --}}
            <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-5">
                <h4 class="font-semibold text-red-700 dark:text-red-300 mb-3 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                    أخطاء شائعة يجب تجنّبها
                </h4>
                <ul class="space-y-2 text-sm text-red-700 dark:text-red-300">
                    <li class="flex gap-2"><span>✗</span> إدخال طول العنبر أصغر من أو يساوي منطقة الخدمات — يسبب خطأ في الحاسبة</li>
                    <li class="flex gap-2"><span>✗</span> نسيان تحديد عدد الأدوار (تأكد أن القيمة 1 على الأقل)</li>
                    <li class="flex gap-2"><span>✗</span> إرسال العرض قبل مراجعة الأسعار النهائية مع المدير</li>
                    <li class="flex gap-2"><span>✗</span> تحويل العرض لعقد قبل اعتماده رسميًا</li>
                    <li class="flex gap-2"><span>✗</span> إضافة وزن طائر غير موجود في جدول الأوزان المعتمدة (1.6، 1.85، 2.1، 2.65، 2.8 كجم)</li>
                </ul>
            </div>

        </div>
    </div>

    {{-- ===== TAB 4: أسئلة شائعة ===== --}}
    <div x-show="tab === 'faq'" x-cloak>
        <div class="space-y-3" x-data="{ open: null }">

            @php
                $faqs = [
                    ['q' => 'ماذا أفعل لو العميل طلب عرضًا لعنبر بطاريات بياض (Layer) وليس تسمين؟', 'a' => 'اختر «نوع المشروع = بياض» في الحاسبة. النظام يستخدم معادلة مختلفة (وحدة العش الطولية 0.6م) ويحسب الأعشاش والطيور والشفاطات بشكل مناسب لبطاريات البياض.'],
                    ['q' => 'كيف أحسب تكلفة عنبر يريد العميل فيه الجزء الميكانيكي فقط بدون مدني؟', 'a' => 'في الحاسبة، اختر «نطاق التسعير = ميكانيكي فقط». سيستبعد النظام بنود الخرسانة والاستيل والحوائط تلقائيًا.'],
                    ['q' => 'العميل يريد تعديل عدد الشفاطات يدويًا — هل يمكن؟', 'a' => 'نعم. في نموذج الحاسبة توجد حقول إدخال يدوي للشفاطات الجانبية والرئيسية. أدخِل العدد مباشرة وستتجاهل الحاسبة حساب المعادلة التلقائي.'],
                    ['q' => 'كيف أعرف أن سعر الطائر في الإعدادات صحيح؟', 'a' => 'اسأل المدير أو تحقق من صفحة الإعدادات (تحتاج صلاحية settings.view). مفتاح الإعداد: poultry_pricing.price_per_bird'],
                    ['q' => 'العميل يطلب خصمًا — أين أضعه؟', 'a' => 'بعد إنشاء عرض السعر، يمكنك تعديل «نسبة الخصم» في رأس العرض. الخصم يُطبَّق على الإجمالي قبل الضريبة.'],
                    ['q' => 'كيف أعرف أن العرض وصل للعميل؟', 'a' => 'اضغط زر «إرسال» في صفحة العرض. يتغير الحالة إلى «مُرسَل» ويُسجَّل التاريخ. يمكنك أيضًا تنزيل PDF ومشاركته يدويًا ثم الضغط على «إرسال» للتسجيل.'],
                    ['q' => 'ما الفرق بين عرض الدواجن وعرض السعر العادي؟', 'a' => 'عرض الدواجن (PoultryQuotation) مرتبط بالحاسبة التقنية — يُدخِل بيانات العنبر ويحسب الكميات تلقائيًا. عرض السعر العادي (Quotation) للبنود الحرة التي تدخلها يدويًا.'],
                    ['q' => 'نسيت إدخال ارتفاع العنبر — هل يؤثر على الحساب؟', 'a' => 'الارتفاع يؤثر فقط على حساب الحوائط (الطول × الارتفاع × 2). لا يؤثر على عدد الأعشاش أو الطيور أو الشفاطات.'],
                    ['q' => 'العميل يريد نسخة بالإنجليزية من العرض — هل ذلك ممكن؟', 'a' => 'النظام يخزن الوصف بالعربي والإنجليزي لكل بند. تأكد من ملء حقل desc_en عند الإنشاء، وعند توليد PDF يمكن اختيار اللغة.'],
                    ['q' => 'ماذا يعني «سعر الصرف» في النظام؟', 'a' => 'يُستخدَم لتحويل المبلغ من الجنيه المصري إلى الدولار في عرض السعر (عمود إضافي). القيمة من الإعداد poultry_pricing.egp_to_usd_rate أو defaults.exchange_rate.'],
                ];
            @endphp

            @foreach ($faqs as $i => $faq)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button
                    @click="open = open === {{ $i }} ? null : {{ $i }}"
                    class="w-full flex items-center justify-between px-4 py-3 text-right hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                >
                    <span class="font-medium text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        <span class="text-primary-600 font-bold">س{{ $i + 1 }}.</span>
                        {{ $faq['q'] }}
                    </span>
                    <x-heroicon-o-chevron-down
                        class="w-5 h-5 text-gray-400 shrink-0 transition-transform"
                        ::class="{ 'rotate-180': open === {{ $i }} }"
                    />
                </button>
                <div x-show="open === {{ $i }}" x-collapse class="px-4 pb-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm text-gray-700 dark:text-gray-300 pt-3 leading-relaxed">
                        <span class="text-primary-600 font-bold ml-1">ج:</span>
                        {{ $faq['a'] }}
                    </p>
                </div>
            </div>
            @endforeach

        </div>
    </div>

    {{-- ===== TAB 5: اختبر نفسك ===== --}}
    <div x-show="tab === 'quiz'" x-cloak>
        <div
            class="max-w-2xl mx-auto"
            wire:key="quiz-section"
        >
            @if (!$quizSubmitted)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="bg-indigo-50 dark:bg-indigo-950/30 px-5 py-4">
                    <h3 class="font-bold text-indigo-800 dark:text-indigo-300 flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                        اختبار سريع — ٥ أسئلة على المثال المرجعي
                    </h3>
                    <p class="text-sm text-indigo-600 dark:text-indigo-400 mt-1">
                        المثال: عنبر {{ $example['barn_length'] }}م × {{ $example['hall_width'] }}م، {{ $example['tiers'] }} أدوار، وزن الطائر {{ $example['bird_weight'] }} كجم
                    </p>
                </div>
                <div class="p-5 space-y-6">

                    @php
                        $questions = [
                            [
                                'key' => 'q1',
                                'text' => 'ما هو الطول الفعّال للعنبر؟',
                                'options' => [
                                    (string)$example['effective_length'] => $example['effective_length'].' م ✓',
                                    (string)($example['effective_length'] + 5) => ($example['effective_length'] + 5).' م',
                                    (string)($example['barn_length']) => $example['barn_length'].' م',
                                    (string)($example['effective_length'] - 10) => ($example['effective_length'] - 10).' م',
                                ],
                            ],
                            [
                                'key' => 'q2',
                                'text' => 'كم عدد الخطوط لعنبر عرضه '.$example['hall_width'].'م؟',
                                'options' => [
                                    '3' => '3 خطوط',
                                    (string)$example['lines'] => $example['lines'].' خطوط ✓',
                                    '5' => '5 خطوط',
                                    '6' => '6 خطوط',
                                ],
                            ],
                            [
                                'key' => 'q3',
                                'text' => 'كم عدد الطيور/العش لوزن '.$example['bird_weight'].' كجم؟',
                                'options' => [
                                    '18' => '18 طيرة',
                                    '21' => '21 طيرة',
                                    (string)$example['birds_per_nest'] => $example['birds_per_nest'].' طيرة ✓',
                                    '13' => '13 طيرة',
                                ],
                            ],
                            [
                                'key' => 'q4',
                                'text' => 'ما هو إجمالي الأعشاش؟',
                                'options' => [
                                    (string)($example['total_nests'] - 200) => number_format($example['total_nests'] - 200).' عش',
                                    (string)$example['total_nests'] => number_format($example['total_nests']).' عش ✓',
                                    (string)($example['total_nests'] + 100) => number_format($example['total_nests'] + 100).' عش',
                                    (string)($example['nests_per_line']) => number_format($example['nests_per_line']).' عش',
                                ],
                            ],
                            [
                                'key' => 'q5',
                                'text' => 'كم عدد الشفاطات الرئيسية المطلوبة؟',
                                'options' => [
                                    (string)($example['main_fans'] - 2) => ($example['main_fans'] - 2).' شفاطات',
                                    (string)($example['main_fans'] + 2) => ($example['main_fans'] + 2).' شفاطات',
                                    (string)$example['main_fans'] => $example['main_fans'].' شفاطات ✓',
                                    (string)($example['main_fans'] + 4) => ($example['main_fans'] + 4).' شفاطات',
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($questions as $qi => $q)
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100 mb-2">
                            <span class="text-indigo-600 font-bold">{{ $qi + 1 }}.</span>
                            {{ $q['text'] }}
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($q['options'] as $val => $label)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition-colors has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/30">
                                <input
                                    type="radio"
                                    name="quiz_{{ $q['key'] }}"
                                    value="{{ $val }}"
                                    wire:model="quizAnswers.{{ $q['key'] }}"
                                    class="text-indigo-600"
                                />
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    <div class="pt-2">
                        <x-filament::button wire:click="submitQuiz" color="primary" size="lg" class="w-full">
                            تحقق من إجاباتي
                        </x-filament::button>
                    </div>
                </div>
            </div>

            @else
            {{-- نتيجة الاختبار --}}
            @php $score = $this->quizScore(); @endphp
            <div class="rounded-xl border {{ $score >= 4 ? 'border-green-300 bg-green-50 dark:bg-green-950/20' : ($score >= 3 ? 'border-yellow-300 bg-yellow-50 dark:bg-yellow-950/20' : 'border-red-300 bg-red-50 dark:bg-red-950/20') }} p-8 text-center space-y-4">
                <div class="text-6xl">{{ $score >= 4 ? '🏆' : ($score >= 3 ? '👍' : '📚') }}</div>
                <h3 class="text-2xl font-bold {{ $score >= 4 ? 'text-green-700' : ($score >= 3 ? 'text-yellow-700' : 'text-red-700') }} dark:text-white">
                    {{ $score }} / 5 إجابات صحيحة
                </h3>
                <p class="text-gray-600 dark:text-gray-300">
                    @if ($score === 5)
                        ممتاز! أتقنتَ المعادلات التقنية كاملاً.
                    @elseif ($score >= 4)
                        جيد جدًا! راجع معادلة واحدة فقط وستكون متقنًا.
                    @elseif ($score >= 3)
                        جيد. راجع قسم «المعادلات الفنية» مجددًا.
                    @else
                        يُنصح بمراجعة كامل قسم المعادلات قبل المتابعة.
                    @endif
                </p>
                <p class="text-sm text-gray-500">الإجابات الصحيحة: طول فعّال={{ $example['effective_length'] }}م، خطوط={{ $example['lines'] }}، طيور/عش={{ $example['birds_per_nest'] }}، أعشاش={{ number_format($example['total_nests']) }}، شفاطات={{ $example['main_fans'] }}</p>
                <x-filament::button wire:click="resetQuiz" color="gray">
                    أعِد الاختبار
                </x-filament::button>
            </div>
            @endif

        </div>
    </div>

</div>
</x-filament-panels::page>
