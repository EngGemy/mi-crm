<x-filament-panels::page>
<div dir="rtl" x-data="{ tab: 'formulas' }" class="space-y-5">

    {{-- Header --}}
    <div class="rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 p-5 text-white shadow-md">
        <div class="flex items-center gap-3">
            <div class="shrink-0 rounded-lg bg-white/15 p-2.5 backdrop-blur-sm">
                <x-heroicon-o-academic-cap class="w-7 h-7 text-white" />
            </div>
            <div>
                <h1 class="text-xl font-bold leading-tight">دليل التدريب — مندوب المبيعات</h1>
                <p class="mt-0.5 text-sm text-primary-100/90">كل ما تحتاجه لإتمام صفقة بطاريات الدواجن — المعادلات والتسعير وخطوات العمل</p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 flex-wrap border-b border-gray-200 dark:border-gray-700 pb-0">
        @php
            $tabs = [
                'formulas' => ['label' => 'المعادلات الفنية', 'icon' => 'heroicon-o-calculator'],
                'pricing'  => ['label' => 'التسعير والضريبة', 'icon' => 'heroicon-o-currency-dollar'],
                'workflow' => ['label' => 'خطوات العمل', 'icon' => 'heroicon-o-arrow-path'],
                'faq'      => ['label' => 'أسئلة شائعة', 'icon' => 'heroicon-o-question-mark-circle'],
                'quiz'     => ['label' => 'اختبر نفسك', 'icon' => 'heroicon-o-clipboard-document-check'],
            ];
        @endphp
        @foreach ($tabs as $key => $meta)
        <button
            @click="tab = '{{ $key }}'"
            :class="tab === '{{ $key }}'
                ? 'border-b-2 border-primary-600 text-primary-600 dark:text-primary-400 font-semibold bg-primary-50/60 dark:bg-primary-900/20'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50'"
            class="flex items-center gap-1.5 px-4 py-2.5 text-sm transition-all rounded-t-lg -mb-px"
        >
            <x-dynamic-component :component="$meta['icon']" class="w-4 h-4" />
            {{ $meta['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ===== TAB 1: المعادلات الفنية ===== --}}
    <div x-show="tab === 'formulas'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        <div class="mb-4 rounded-lg bg-primary-50 dark:bg-primary-900/15 border border-primary-200 dark:border-primary-800/50 p-4">
            <p class="text-sm text-primary-800 dark:text-primary-200 leading-relaxed">
                <strong>المثال المرجعي:</strong>
                عنبر طوله <strong>{{ $example['barn_length'] }}م</strong> × عرض <strong>{{ $example['hall_width'] }}م</strong>،
                منطقة خدمات <strong>{{ $example['service_length'] }}م</strong>،
                <strong>{{ $example['tiers'] }} أدوار</strong>،
                وزن الطائر <strong>{{ $example['bird_weight'] }} كجم</strong>.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @php
                $formulas = [
                    [
                        'num' => '٠١',
                        'title' => 'الطول الفعّال',
                        'formula' => 'الطول الفعّال = طول العنبر − منطقة الخدمات',
                        'example' => "{$example['barn_length']} − {$example['service_length']} = {$example['effective_length']} م",
                        'note' => 'منطقة الخدمات تُقرأ من الإعداد: poultry_pricing.default_service_length (افتراضي: '.$example['service_length'].' م)',
                    ],
                    [
                        'num' => '٠٢',
                        'title' => 'عدد الخطوط من العرض',
                        'formula' => "12م → 4 خطوط | 15م → 5 خطوط | 16.5م → 6 خطوط",
                        'example' => "عرض {$example['hall_width']}م → {$example['lines']} خطوط",
                        'note' => 'الخريطة مخزّنة في الإعداد width_lines_map، أو يمكن إدخال عدد الخطوط مباشرة',
                    ],
                    [
                        'num' => '٠٣',
                        'title' => 'عدد الطيور/العش (تسمين)',
                        'formula' => "1.6 كجم → 21 | 1.85 → 18 | 2.1 → 16 | 2.65 → 13 | 2.8 → 12",
                        'example' => "وزن {$example['bird_weight']} كجم → {$example['birds_per_nest']} طيور/عش",
                        'note' => 'الخريطة من الإعداد broiler_weight_birds_map — أوزان مختلفة تعطي كثافات مختلفة',
                    ],
                    [
                        'num' => '٠٤',
                        'title' => 'عدد الأعشاش/الخط',
                        'formula' => 'أعشاش/الخط = الطول الفعّال × 2 (وجهان) × عدد الأدوار',
                        'example' => "{$example['effective_length']} × 2 × {$example['tiers']} = {$example['nests_per_line']} عش/خط",
                        'note' => 'الوجهان: الأعشاش تُركَّب على جانبَي الخط (وجه أمامي + خلفي)',
                    ],
                    [
                        'num' => '٠٥',
                        'title' => 'إجمالي الأعشاش',
                        'formula' => 'إجمالي الأعشاش = أعشاش/الخط × عدد الخطوط',
                        'example' => "{$example['nests_per_line']} × {$example['lines']} = ".number_format($example['total_nests'])." عش",
                        'note' => 'هذا الرقم يحدد سعة العنبر الكلية قبل احتساب كثافة الطيور',
                    ],
                    [
                        'num' => '٠٦',
                        'title' => 'إجمالي الطيور (السعة)',
                        'formula' => 'إجمالي الطيور = إجمالي الأعشاش × عدد الطيور/العش',
                        'example' => number_format($example['total_nests'])." × {$example['birds_per_nest']} = ".number_format($example['total_birds'])." طيرة",
                        'note' => 'هذا هو الرقم الأساسي الذي يُبنى عليه تسعير البطاريات والشفاطات',
                    ],
                    [
                        'num' => '٠٧',
                        'title' => 'عدد الشفاطات الرئيسية',
                        'formula' => 'الشفاطات = تقريب لأعلى ( إجمالي الطيور × أقصى وزن للتهوية ÷ سعة الشفاطة )',
                        'example' => $example['fan_formula'],
                        'note' => "سعة الشفاطة = {$example['fan_capacity']} كجم (من الإعداد fan_capacity_kg). الحمل = ".number_format($example['fan_load_kg'])." كجم",
                    ],
                    [
                        'num' => '٠٨',
                        'title' => 'طول وحدات التبريد',
                        'formula' => 'التبريد (م) = تقريب لأعلى ( عدد الشفاطات × '.number_format($example['cooling_per_fan'], 1).' )',
                        'example' => "ceil({$example['main_fans']} × {$example['cooling_per_fan']}) = {$example['cooling_pad']} م",
                        'note' => "المعامل ".number_format($example['cooling_per_fan'], 1)." م/شفاطة مأخوذ من الإعداد cooling_pad_meters_per_fan",
                    ],
                    [
                        'num' => '٠٩',
                        'title' => 'شبابيك الهواء (Inlets) — تسمين',
                        'formula' => "إذا طول العنبر فردي: (الطول − 3) ÷ 2\nإذا طول العنبر زوجي: (الطول − 4) ÷ 2",
                        'example' => "{$example['barn_length']}م (زوجي) → ({$example['barn_length']} − 4) ÷ 2 = {$example['air_windows']} شباك",
                        'note' => 'الشبابيك تُوضع على الجانبين لضمان تهوية طولية متوازنة داخل العنبر',
                    ],
                    [
                        'num' => '١٠',
                        'title' => 'أعشاش البياض (Layer)',
                        'formula' => 'أعشاش وجه واحد = الطول الفعّال ÷ وحدة العش (0.60م افتراضي)',
                        'example' => "70 ÷ 0.60 = 116 عش/وجه → أعشاش/خط = 116 × 2 وجه × أدوار",
                        'note' => 'البياض يستخدم وحدة طولية ثابتة بدلاً من خريطة الوزن. وحدة العش من الإعداد layer_nest_module_m',
                    ],
                ];
            @endphp

            @foreach ($formulas as $f)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div class="bg-gray-50 dark:bg-gray-800/60 px-4 py-3 flex items-center gap-3 border-b border-gray-100 dark:border-gray-800">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-primary-100 dark:bg-primary-900/40 text-xs font-bold text-primary-700 dark:text-primary-300">{{ $f['num'] }}</span>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 text-sm">{{ $f['title'] }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 p-3 font-mono text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $f['formula'] }}</div>
                    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/15 border border-emerald-100 dark:border-emerald-800/40 p-3">
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 font-medium mb-1">مثال محلول:</p>
                        <p class="font-semibold text-emerald-800 dark:text-emerald-300 text-sm">{{ $f['example'] }}</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-start gap-1.5 leading-relaxed">
                        <x-heroicon-o-light-bulb class="w-4 h-4 shrink-0 text-amber-500 mt-0.5" />
                        {{ $f['note'] }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ===== TAB 2: التسعير والضريبة ===== --}}
    <div x-show="tab === 'pricing'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="space-y-5">

            {{-- Pricing Table --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800/60 px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2 text-sm">
                        <x-heroicon-o-banknotes class="w-4 h-4 text-emerald-600" />
                        بنود التسعير — كيف تُحسب القيمة؟
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">البند</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">الوحدة</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">الكمية</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">سعر الوحدة</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-600 dark:text-gray-300 text-xs uppercase tracking-wider">الإجمالي</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">المعادلة</th>
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
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $row[0] }}</td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $row[1] }}</td>
                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $row[2] }}</td>
                                <td class="px-4 py-2.5 font-mono text-gray-600 dark:text-gray-400 text-xs">{{ $row[3] }}</td>
                                <td class="px-4 py-2.5 font-bold text-emerald-700 dark:text-emerald-400 font-mono text-sm">{{ $row[4] }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-400 dark:text-gray-500">{{ $row[5] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Financial Summary & Rules --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5">
                    <h4 class="font-semibold mb-4 text-gray-700 dark:text-gray-200 text-sm">الملخص المالي (مثال)</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">المجموع قبل الضريبة</span>
                            <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">{{ number_format($example['subtotal']) }} ج.م</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">ضريبة القيمة المضافة ({{ number_format($example['vat_rate'], 0) }}٪)</span>
                            <span class="font-mono text-amber-600 dark:text-amber-400">{{ number_format($example['vat_amount']) }} ج.م</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between items-center">
                            <span class="font-bold text-base">الإجمالي</span>
                            <span class="font-mono font-bold text-lg text-primary-600 dark:text-primary-400">{{ number_format($example['total']) }} ج.م</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/60 dark:bg-amber-950/15 p-5 space-y-3">
                    <h4 class="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-2 text-sm">
                        <x-heroicon-o-eye-slash class="w-4 h-4" />
                        قاعدة إخفاء سعر الطائر للوحدة
                    </h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300/90 leading-relaxed">
                        بند البطاريات يظهر في عرض السعر كـ <strong>«مقطوعة»</strong> (Lot) بدون إظهار سعر الطائر الواحد.
                        المبلغ الإجمالي = عدد الطيور × سعر الطائر يُحسب داخل النظام ويُعرض كمبلغ إجمالي فقط.
                    </p>
                    <p class="text-xs text-amber-600/80 dark:text-amber-400/70 leading-relaxed">
                        السبب: سعر الطائر معلومة تجارية حساسة. العميل يرى المبلغ الكلي للبطاريات فقط.
                    </p>
                </div>
            </div>

            {{-- VAT Info --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-4 text-sm flex items-center gap-2">
                    <x-heroicon-o-receipt-percent class="w-4 h-4 text-primary-600" />
                    ضريبة القيمة المضافة (VAT)
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">14٪</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">مصر</p>
                    </div>
                    <div class="text-center rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">15٪</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">المملكة العربية السعودية</p>
                    </div>
                    <div class="text-center rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">0٪</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">معفاة</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">النسبة الحالية المطبّقة: {{ number_format($vatRate, 0) }}٪ — تُقرأ من الإعداد tax.vat_rate_egypt/ksa</p>
            </div>
        </div>
    </div>

    {{-- ===== TAB 3: خطوات العمل ===== --}}
    <div x-show="tab === 'workflow'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="space-y-0 relative">
            {{-- Timeline connector --}}
            <div class="absolute right-[27px] top-4 bottom-4 w-px bg-gray-200 dark:bg-gray-700 hidden md:block"></div>

            @php
                $steps = [
                    [
                        'num' => '1',
                        'icon' => 'heroicon-o-user-plus',
                        'title' => 'إنشاء فرصة بيع (Lead)',
                        'desc' => 'من قائمة «العملاء المحتملون» → جديد. أدخِل: الاسم، الشركة، رقم الواتساب، المصدر (معرض/إحالة/إعلان)، القيمة المتوقّعة، المندوب المسؤول.',
                        'tip' => 'كلّما كانت بيانات الـ Lead مكتملة، كلّما ساعدك النظام في التتبع والتنبيهات.',
                    ],
                    [
                        'num' => '2',
                        'icon' => 'heroicon-o-calculator',
                        'title' => 'إدخال بيانات العنبر في الحاسبة',
                        'desc' => 'من «عروض الدواجن» → جديد. أدخِل: طول/عرض/ارتفاع العنبر، عدد الأدوار، وزن الطائر المستهدف. النظام يحسب الكميات تلقائيًا.',
                        'tip' => 'تأكد من أن طول العنبر أكبر من منطقة الخدمات (10م افتراضي). إذا لم يكن هناك خدمات اجعلها 0.',
                    ],
                    [
                        'num' => '3',
                        'icon' => 'heroicon-o-document-text',
                        'title' => 'توليد عرض السعر',
                        'desc' => 'بعد إدخال البيانات، اضغط «حساب وحفظ». النظام يولّد البنود بأسعار تلقائية. راجع البنود وعدّل أي قيم إذا لزم، ثم احفظ العرض.',
                        'tip' => 'يمكنك تعديل الكميات أو الأسعار يدويًا بعد الحساب التلقائي.',
                    ],
                    [
                        'num' => '4',
                        'icon' => 'heroicon-o-share',
                        'title' => 'مشاركة العرض مع العميل',
                        'desc' => 'من صفحة العرض: «تنزيل PDF» لإرساله بالبريد أو واتساب، أو «صورة» لإرساله مباشرة عبر المحادثة، أو اضغط «إرسال» للتسجيل في النظام.',
                        'tip' => 'بعد الإرسال يتحوّل حالة العرض إلى «مُرسَل» ويُسجَّل في سجل التدقيق تلقائيًا.',
                    ],
                    [
                        'num' => '5',
                        'icon' => 'heroicon-o-check-badge',
                        'title' => 'اعتماد العميل للعرض',
                        'desc' => 'عند موافقة العميل، اضغط «اعتماد» في صفحة العرض. يتحوّل الحالة إلى «معتمد». الآن يمكنك تحويله لعقد.',
                        'tip' => 'الاعتماد لا يتطلب توقيعًا ورقيًا — هو مجرد تسجيل موافقة العميل في النظام.',
                    ],
                    [
                        'num' => '6',
                        'icon' => 'heroicon-o-document-check',
                        'title' => 'تحويل العرض إلى عقد',
                        'desc' => 'من صفحة العرض المعتمد: اضغط «تحويل لعقد». النظام ينشئ عقدًا بكل بنود العرض تلقائيًا. راجع بنود العقد وأضِف الشروط والبنود القانونية.',
                        'tip' => 'التحويل يُسجَّل تلقائيًا في سجل التدقيق كحدث «تحويل».',
                    ],
                    [
                        'num' => '7',
                        'icon' => 'heroicon-o-banknotes',
                        'title' => 'جدول الدفعات والمتابعة',
                        'desc' => 'من صفحة العقد، حدد جدول الدفعات (دفعة مقدمة، دفعات مرحلية، دفعة نهائية). النظام يتابع الدفعات المستحقة ويُنبّهك عند الاستحقاق.',
                        'tip' => 'يمكن المحاسب تسجيل الدفعات من قائمة «الدفعات» مباشرة.',
                    ],
                    [
                        'num' => '8',
                        'icon' => 'heroicon-o-arrow-path',
                        'title' => 'المتابعة الدورية',
                        'desc' => 'راجع صفحة «العملاء المحتملون» يوميًا. العملاء الحمراء (بدون تواصل منذ فترة) يحتاجون متابعة فورية. استخدم أزرار الواتساب والاتصال السريع.',
                        'tip' => 'النظام يُنبّهك تلقائيًا للعملاء المحتملين الذين لم يُتواصَل معهم منذ فترة.',
                    ],
                ];
            @endphp

            @foreach ($steps as $step)
            <div class="flex gap-4 rounded-xl bg-white dark:bg-gray-900 p-4 md:pr-5 border border-gray-100 dark:border-gray-800 mb-3 relative hover:border-gray-200 dark:hover:border-gray-700 transition-colors">
                <div class="shrink-0 w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 border-2 border-white dark:border-gray-800 flex items-center justify-center z-10 shadow-sm">
                    <span class="text-xs font-bold text-primary-700 dark:text-primary-300">{{ $step['num'] }}</span>
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                    <div class="flex items-center gap-2 mb-1.5">
                        <x-dynamic-component :component="$step['icon']" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 text-sm">{{ $step['title'] }}</h4>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 leading-relaxed">{{ $step['desc'] }}</p>
                    <div class="flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-lg px-3 py-2 border border-gray-100 dark:border-gray-700/60">
                        <x-heroicon-o-light-bulb class="w-3.5 h-3.5 shrink-0 mt-0.5 text-amber-500" />
                        {{ $step['tip'] }}
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Common Mistakes --}}
            <div class="rounded-xl border border-red-200 dark:border-red-800/60 bg-red-50/60 dark:bg-red-950/15 p-5 mt-4">
                <h4 class="font-semibold text-red-700 dark:text-red-300 mb-3 flex items-center gap-2 text-sm">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                    أخطاء شائعة يجب تجنّبها
                </h4>
                <ul class="space-y-2 text-sm text-red-700 dark:text-red-300/90">
                    <li class="flex gap-2 items-start"><span class="mt-0.5">•</span> إدخال طول العنبر أصغر من أو يساوي منطقة الخدمات — يسبب خطأ في الحاسبة</li>
                    <li class="flex gap-2 items-start"><span class="mt-0.5">•</span> نسيان تحديد عدد الأدوار (تأكد أن القيمة 1 على الأقل)</li>
                    <li class="flex gap-2 items-start"><span class="mt-0.5">•</span> إرسال العرض قبل مراجعة الأسعار النهائية مع المدير</li>
                    <li class="flex gap-2 items-start"><span class="mt-0.5">•</span> تحويل العرض لعقد قبل اعتماده رسميًا</li>
                    <li class="flex gap-2 items-start"><span class="mt-0.5">•</span> إضافة وزن طائر غير موجود في جدول الأوزان المعتمدة (1.6، 1.85، 2.1، 2.65، 2.8 كجم)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ===== TAB 4: أسئلة شائعة ===== --}}
    <div x-show="tab === 'faq'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="space-y-2" x-data="{ open: null }">
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
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden transition-all hover:border-gray-300 dark:hover:border-gray-600">
                <button
                    @click="open = open === {{ $i }} ? null : {{ $i }}"
                    class="w-full flex items-center justify-between px-5 py-3.5 text-right hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                >
                    <span class="font-medium text-gray-800 dark:text-gray-100 text-sm flex items-center gap-2">
                        <span class="text-primary-600 font-bold text-xs">{{ sprintf('%02d', $i + 1) }}.</span>
                        {{ $faq['q'] }}
                    </span>
                    <x-heroicon-o-chevron-down
                        class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200 mr-2"
                        ::class="{ 'rotate-180': open === {{ $i }} }"
                    />
                </button>
                <div x-show="open === {{ $i }}" x-collapse class="px-5 pb-4 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-300 pt-3 leading-relaxed">
                        {{ $faq['a'] }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ===== TAB 5: اختبر نفسك ===== --}}
    <div x-show="tab === 'quiz'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="max-w-2xl mx-auto" wire:key="quiz-section">
            @if (!$quizSubmitted)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800/60 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2 text-sm">
                        <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-primary-600" />
                        اختبار سريع — ٥ أسئلة على المثال المرجعي
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        المثال: عنبر {{ $example['barn_length'] }}م × {{ $example['hall_width'] }}م، {{ $example['tiers'] }} أدوار، وزن الطائر {{ $example['bird_weight'] }} كجم
                    </p>
                </div>
                <div class="p-5 space-y-6">
                    @php
                        $correctMap = $quizQuestions['correctMap'] ?? [];
                        $questions = $quizQuestions['questions'] ?? [];
                    @endphp

                    @foreach ($questions as $qi => $q)
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100 mb-2.5 text-sm">
                            <span class="text-primary-600 font-bold ml-1">{{ $qi + 1 }}.</span>
                            {{ $q['text'] }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($q['options'] as $val => $label)
                            <label class="group flex items-center gap-2.5 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 cursor-pointer hover:bg-primary-50/50 dark:hover:bg-primary-900/15 transition-all has-[:checked]:border-primary-400 dark:has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 dark:has-[:checked]:bg-primary-900/20 has-[:checked]:ring-1 has-[:checked]:ring-primary-400 dark:has-[:checked]:ring-primary-600">
                                <input
                                    type="radio"
                                    name="quiz_{{ $q['key'] }}"
                                    value="{{ $val }}"
                                    wire:model="quizAnswers.{{ $q['key'] }}"
                                    class="text-primary-600 border-gray-300 focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300 group-has-[:checked]:text-primary-700 dark:group-has-[:checked]:text-primary-300">{{ $label }}</span>
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
            @php
                $score = $this->quizScore();
                $resultQuestions = $quizQuestions['questions'] ?? [];
                $resultCorrectMap = $quizQuestions['correctMap'] ?? [];
            @endphp
            <div class="rounded-xl border {{ $score >= 4 ? 'border-green-300 dark:border-green-700' : ($score >= 3 ? 'border-amber-300 dark:border-amber-700' : 'border-red-300 dark:border-red-700') }} {{ $score >= 4 ? 'bg-green-50 dark:bg-green-950/15' : ($score >= 3 ? 'bg-amber-50 dark:bg-amber-950/15' : 'bg-red-50 dark:bg-red-950/15') }} p-8 text-center space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full {{ $score >= 4 ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : ($score >= 3 ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600' : 'bg-red-100 dark:bg-red-900/30 text-red-600') }}">
                    <x-dynamic-component :component="$score >= 4 ? 'heroicon-o-trophy' : ($score >= 3 ? 'heroicon-o-hand-thumb-up' : 'heroicon-o-book-open')" class="w-8 h-8" />
                </div>
                <h3 class="text-2xl font-bold {{ $score >= 4 ? 'text-green-700 dark:text-green-300' : ($score >= 3 ? 'text-amber-700 dark:text-amber-300' : 'text-red-700 dark:text-red-300') }}">
                    {{ $score }} / 5 إجابات صحيحة
                </h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm">
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

                {{-- Detailed Results --}}
                <div class="text-right max-w-md mx-auto mt-4 space-y-2">
                    @foreach ($resultQuestions as $qi => $q)
                        @php
                            $selected = $quizAnswers[$q['key']] ?? null;
                            $correctVal = $resultCorrectMap[$q['key']] ?? null;
                            $isCorrect = $selected === $correctVal;
                        @endphp
                        <div class="flex items-center gap-2 text-sm {{ $isCorrect ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                            @if ($isCorrect)
                                <x-heroicon-o-check-circle class="w-4 h-4 shrink-0" />
                            @else
                                <x-heroicon-o-x-circle class="w-4 h-4 shrink-0" />
                            @endif
                            <span>السؤال {{ $qi + 1 }}: {{ $isCorrect ? 'صحيح' : 'خاطئ' }} — الإجابة الصحيحة: {{ $q['options'][$correctVal] ?? $correctVal }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-2">
                    <x-filament::button wire:click="resetQuiz" color="gray">
                        أعِد الاختبار
                    </x-filament::button>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>
</x-filament-panels::page>
