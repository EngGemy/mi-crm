# خطة عرض سعر البياض — مرحلة 0 (تحليل فقط)

تاريخ: 2026-10-07. لا يوجد تغيير كود في هذه المرحلة.

المسار الحي لحاسبة الأسعار هو عرض `PoultryQuotation` في Filament، وملف PDF يُبنى بـ `MiProposalPdfGenerator` فوق قالب التسمين الثابت (13 صفحة). عمود `quote_type_id` موجود وغير مستخدم. تمييز البياض الموجود فعلًا هو `project_type`.

خط أحمر: `MiProposalPdfGenerator`، `config/mi_proposal.php`، قوالب `resources/views/poultry/proposal/*`، وملف `storage/app/pdf-templates/mi-proposal-template.pdf` لا تُمس. قالب التسمين يبقى المخرج الافتراضي.

---

## 1. خريطة الكود الحالي

### الحاسبة (Filament)

| ملف | الدور |
|---|---|
| `app/Filament/Resources/PoultryQuotationResource.php` | معالج عرض السعر: عميل، نوع المشروع، نطاق التسعير، أبعاد العنبر، والحساب الحي. أزرار PDF وكارت واتساب. |
| `app/Filament/Concerns/HasLivePoultryPricing.php` | يعيد الحساب عند تغيير الحقول ويستدعي خدمة التسعير. |
| `app/Filament/Resources/PoultryQuotationResource/Pages/CreatePoultryQuotation.php` | إنشاء العرض. |
| `app/Filament/Resources/PoultryQuotationResource/Pages/EditPoultryQuotation.php` | تعديل العرض. |
| `app/Filament/Resources/PoultryQuotationResource/Pages/ViewPoultryQuotation.php` | عرض السجل + توليد كارت المشاركة. |
| `app/Filament/Resources/PoultryQuotationResource/Pages/ListPoultryQuotations.php` | القائمة. |
| `app/Filament/Resources/LookupResource.php` | إدارة جداول `lookups` (نوع العرض، سيور، سايلو، موقع). |
| `app/Filament/Pages/CompanySettings.php` | إعدادات فنية للبياض: أقصى وزن طائر وارتفاعات العنبر. |

### الموديل والبيانات

| ملف | الدور |
|---|---|
| `app/Models/PoultryQuotation.php` | سجل الحاسبة. `project_type`، `quote_type_id` (غير مملوء من الواجهة)، التكاليف، `pricing_snapshot`، ورابط واتساب. |
| `app/Models/Lookup.php` | كتالوج الأكواد. `TYPE_QUOTE_TYPE = quote_type`. اليوم كود واحد: `batteries_broiler_eg`. |
| `app/Models/PoultryQuotationSnapshot.php` | لقطة محفوظة بجانب السجل. |
| `app/Enums/PoultryProjectType.php` | `broiler`، `broiler_auto_exit`، `layer`، `layer_auto_collect`، `layer_rearing`. `pricesAs()` يسعّر الجمع الآلي وتربية البياض كإنتاج بياض. |
| `app/Enums/PoultryPricingScope.php` | نطاق التسعير (بطاريات فقط / مشروع كامل / …). |
| `database/seeders/LookupSeeder.php` | يزرع `batteries_broiler_eg` فقط داخل `quote_type`. |
| `database/migrations/2026_05_15_000002_create_poultry_quotations_table.php` | جدول الحاسبة. |
| `database/migrations/2026_10_04_000002_extend_poultry_quotations_for_quote_wizard.php` | يضيف `quote_type_id` وعلاقات السيور/السايلو/الموقع. |
| `database/migrations/2026_05_19_000001_add_calculator_fields_to_poultry_quotations.php` | حقول الحساب (خطوط، أدوار، سعة، تكاليف). |
| `database/migrations/2026_10_05_000003_add_internal_columns_and_bird_price_usd_to_poultry_quotations.php` | سعر الطائر بالدولار والأعمدة الداخلية. |
| `database/migrations/2026_07_06_000001_add_optional_accessory_costs_to_poultry_quotations.php` | تكاليف كماليات اختيارية. |

### الحساب

| ملف | الدور |
|---|---|
| `app/Services/Poultry/PoultryTechnicalCalculator.php` | كميات التسمين والبياض (طول فعال، أعشاش، طيور، شفاطات). تربية البياض ترمي استثناء: الحاسبة غير مفعّلة. |
| `app/Services/PoultryHousePricingService.php` | يبني بنود `pricing_snapshot` (خرسانة، بطاريات، تهوية…) ويحسب VAT عبر `FinancialEngine` بخصم 0. |
| `app/Services/Pricing/PricingCalculator.php` | واجهة حساب قديمة حول نفس المدخلات. |
| `app/Services/Pricing/DTOs/QuotationInput.php` | مدخلات الحساب. |
| `app/Services/Pricing/DTOs/QuotationResult.php` | مخرجات الحساب. |
| `app/Http/Controllers/Api/PoultryPricingController.php` | `POST /api/poultry/calculate`. |
| `app/Support/FinancialEngine.php` | subtotal ثم خصم ثم VAT ثم إجمالي. الحاسبة تمرّر خصم 0. |
| `app/Support/TaxResolver.php` | نسبة VAT من الإعدادات (مصر 14٪ افتراضيًا). |

### قالب التسمين الحالي (خط أحمر)

| ملف | الدور |
|---|---|
| `app/Services/Poultry/MiProposalPdfGenerator.php` | PDF الحي: يستورد 13 صفحة من قالب ثابت، ويعيد رسم الصفحات 2 و6 و9 و10 فقط. |
| `config/mi_proposal.php` | مسار القالب، منطقة الرسم، ثوابت القفص، وبنود «العرض شامل». |
| `app/Services/Poultry/ProposalPage2Data.php` | صفحة الغلاف: عميل، نوع، أبعاد، موقع. |
| `app/Services/Poultry/ProposalPage6Data.php` | سيور السبلة وعدد المواتير. |
| `app/Services/Poultry/ProposalPage9Data.php` | مواصفات البطارية والقفص. |
| `app/Services/Poultry/ProposalPage10Data.php` | البند المالي: سطر واحد + subtotal + VAT + total بالدولار والجنيه. |
| `app/Services/Poultry/ProposalSnapshotFreezer.php` | يثبت الشروط داخل `pricing_snapshot.terms` عند الحفظ. |
| `resources/views/poultry/proposal/page2.blade.php` | HTML صفحة 2. |
| `resources/views/poultry/proposal/page6-motors.blade.php` | HTML المواتير. |
| `resources/views/poultry/proposal/page6-belts.blade.php` | HTML السيور. |
| `resources/views/poultry/proposal/page9.blade.php` | HTML المواصفات. |
| `resources/views/poultry/proposal/page10.blade.php` | HTML البند المالي. |
| `routes/web.php` | `poultry-quotations.pdf` و`poultry-quotations.welcome-pdf` يستدعيان المولّد مباشرة. |
| `tests/Unit/MiProposalPdfGeneratorTest.php` | اختبار قالب التسمين. |
| `tests/Unit/ProposalPage6DataTest.php` | اختبار بيانات صفحة 6. |

### PDF آخر غير مستخدم في مسار الحاسبة

| ملف | الدور |
|---|---|
| `app/Services/PoultryQuotationPdfGenerator.php` | PDF مبسّط عبر `laravel-mpdf`. لا يستدعيه أي route. |
| `resources/views/poultry/quotation-pdf.blade.php` | قالبه. خارج مسار التسمين ذي الـ 13 صفحة. لا يُعدَّل في هذه الخطة. |

### واتساب والكارت (مرحلة لاحقة)

| ملف | الدور |
|---|---|
| `app/Services/Poultry/PoultryWelcomeWhatsApp.php` | نص الرسالة + رابط PDF موقّع (`signed`). |
| `app/Services/Pricing/PricingCardImageGenerator.php` | كارت مربع عبر mPDF ثم PNG إن وُجد Imagick. |
| `resources/views/pricing-calculator/card.blade.php` | شكل الكارت الحالي (خلفية كحلية، بدون هوية واتساب). |

### خارج النطاق

وحدة `Quotation` / `hall_type = بياض` و`Product.category` خاصة بعروض المبيعات والعقود القديمة. ليست حاسبة `PoultryQuotation`. لا تُستخدم لتمييز قالب البياض.

---

## 2. تمييز الأصناف

لا يوجد صنف سطر (line item) نوعه «بياض». العرض له نوع واحد:

- المصدر الفعلي: `poultry_quotations.project_type`.
- بياض: `layer`، `layer_auto_collect`، `layer_rearing` (الأخير محسوب سعريًا كبياض، والحاسبة الفنية له غير مفعّلة).
- تسمين: `broiler`، `broiler_auto_exit`، والقيمة الفارغة تبقى تسمين كما اليوم (`??= broiler`).
- `quote_type_id` عمود جاهز + علاقة `quoteType()`، والفورم لا يكتبه. الـ seeder فيه `batteries_broiler_eg` فقط. الصفوف الحالية قيمتها `null`.
- `Product.category` (بطاريات، تهوية، سباكة…) بلا علم بياض/تسمين.
- بنود `pricing_snapshot.items` مفاتيح مشتركة (`battery`، `concrete`…) وليست مصنّفة بياض.

الطريقة التي لا تكسر الداتا:

1. إضافة `PoultryProjectType::isLayer()` فقط. لا تغيير في `pricesAs()` ولا في معادلات التسمين.
2. الـ Resolver يقرأ `project_type` فقط. أي قيمة ليست بياضًا — بما فيها `null` — ترجع قالب التسمين الحالي.
3. زرع lookup جديد `batteries_layers_eg` بالإضافة، بدون تعطيل `batteries_broiler_eg` وبدون backfill للصفوف القديمة.
4. عند حفظ عرض بياض جديد فقط: إذا `quote_type_id` فارغ يُملأ بالlookup الجديد. عرض التسمين لا يُكتب له `quote_type_id`.

لا نجعل الـ lookup مفتاح التحويل. مفتاحان يخلقان تعارضًا (نوع مشروع تسمين + كود بياض). `project_type` هو المصدر.

---

## 3. تحليل قالب البياض

المصدر: `docs/quotations/layers/layers_quotation.md` فقط. الصور عائمة في الورد، لذلك الربط صورة↔قسم استنتاج من ترتيب الـ markdown لا من فتح الملفات.

### الأقسام بالترتيب

| # | القسم | صور مرتبطة (من موضعها في الملف) | طبيعة النص |
|---|---|---|---|
| 0 | ترويسة الصفحة `header1.xml` | `image23.jpg`، `image24.png` | ثابت (هوية الصفحة) |
| 1 | غلاف: «عرض مالي وفني…» + تفاصيل العنبر | `image1.png`، `image2.png` قبل العنوان | عنوان ثابت. العميل والأبعاد والموقع ديناميكية. نوع العنبر في العينة: بياض |
| 2 | أبعاد العش + سلك مجلفن 275 جم / 2.7 مم | `image3.png` قبل العنوان | مواصفات ثابتة |
| 3 | سلك شبك | ضمن عنقود `image4.png`–`image14.jpg` | ثابت |
| 4 | باب القفص | نفس العنقود | نص ثابت. العينة: 60 × 65 × 45 سم |
| 5 | ترولي العلف | نفس العنقود | ثابت (1 مم، ماتور 0.5 حصان) |
| 6 | العلافات | نفس العنقود | ثابت (0.8 مم، حامل 2 مم كل 60 سم) |
| 7 | خطوط المياه | نفس العنقود | ثابت (مواسير 22×22، 3 نبل، V-CUP) |
| 8 | أرضيات | نفس العنقود | ثابت (ميل 7 درجات، 275 جم زنك) |
| 9 | دولاب السبلة + السير العرضي | آخر العنقود ثم عنوان السير | جزء ثابت. طول داخلي 12 م وخارجي 8 م يأتي من lookups |
| 10 | جمع البيض + دولاب البيض | `image15.png`، `image16.png` | ثابت (سير 10 سم، 1.5 حصان، 9 لفات/د) |
| 11 | سايلو العلف | `image17.jpg`، `image18.png`، `image19.jpg` | تخانة 2 مم ثابتة. السعة في العينة 11 طن = lookup السايلو |
| 12 | بريمة العلف | بعد صور السايلو | ثابت (4 بوصة، 10 م، 1 حصان، خزان 100 كجم) |
| 13 | لوحات الكهرباء SEMINS | `image20.jpg`، `image21.jpg` | ثابت |
| 14 | منظومة السبلة + منظومة التغذية | لا صور بعد اللوحات | ثابت (ريموت 80 م، حساسات ألمانية) |
| 15 | جدول المواصفات الفنية | لا صور | أرقام ديناميكية من الحاسبة |
| 16 | البند المالي | لا صور | وصف ثابت + مبالغ ديناميكية |
| 17 | العرض شامل + مدة الارتباط | لا صور | ثابت (3 أيام، زنك 275، ضمان 12 عام) |
| 18 | بند الضمان | لا صور | ثابت (12 شهر تصنيع + 12 سنة صاج/سلك) |
| 19 | بند التركيب | لا صور | ثابت. الإقامة على العميل |
| 20 | بند قطع الغيار | لا صور | ثابت. النص يذكر 1٪ داخل وصف البند المالي وليس سطر سعر |
| 21 | بند التعاقد / الدفعات | لا صور | ثابت: 70٪ تعاقد، 25٪ تركيب، 5٪ آخر سيارة |
| 22 | دليل التطهير | لا صور | ثابت بالكامل |
| 23 | خدمة ما بعد البيع | لا صور | ثابت (دمياط، واتساب، 24/7) |

`image22.png` غير مذكور في جسم الـ markdown. يُعامل كأصل غير مستخدم حتى تأكيد لاحق، ولا يُضمَّن في الـ PDF.

### الحقول الديناميكية

عينة الملف: عميل «م/ محمد جامع»، بياض، طول 81 م، عرض 11.5 م، ارتفاع 3.5 م، دمياط، 4 خطوط × 4 أدوار، 120 قفص/جهة، 3840 قفص، 10 طيور، 38400 طائر، USD 130,560، EGP 6,789,120، تاريخ 01/10/2026.

التحقق الداخلي (لا يُكتب في القالب كرقم ثابت): طول 81 م − حوالي 8 م خدمات، ثم ضبط على 0.60 م بعدد وحدات زوجي = **72 م** طول فعال. 72 / 0.60 = **120** قفصًا للجهة الواحدة. 120 × 2 × 4 أدوار × 4 خطوط = **3840** قفصًا. × 10 = **38400** طائر. النص المشوّه «متر7 2» في الملف يُقرأ 72 متر، والمصدر هو الحاسبة لا النص الحرفي.

| الحقل | مثال من الملف | المصدر المقترح | موجود؟ |
|---|---|---|---|
| اسم العميل | م/ محمد جامع | `client_name` | نعم |
| تاريخ العرض | 01/10/2026 | `issued_at` | نعم |
| رقم/معرّف العميل | CUSTOMER ID = اسم العميل | `quote_number` للرقم، والاسم للعرض كما في صفحة 10 الحالية | نعم |
| نوع العنبر | بياض | `project_type` → `labelAr()` | نعم |
| طول العنبر | 81 متر | `length` / snapshot `barn_length` | نعم |
| عرض داخلي | 11,5 متر | `width` | نعم |
| ارتفاع | 3,5 متر | `height` | نعم |
| مكان المشروع | دمياط | `client_location` | نعم |
| الطول الفعال | 72 متر (بعد تصحيح النص) | `technical.effective_length` | نعم |
| عدد الأدوار | 4 | `tiers` | نعم |
| عدد الخطوط | 4 | `lines` | نعم |
| أقفاص الجهة الواحدة | 120 | `nests_one_side` | نعم |
| أقفاص العنبر | 3,840 | `total_nests` | نعم |
| طيور القفص | 10 | `birds_per_nest` (افتراض البياض 10) | نعم |
| قطيع العنبر | 38,400 | `bird_count` / `total_birds` | نعم |
| سعة السايلو | 11 طن | `silo_capacity_id` → lookup | نعم |
| سير داخلي / خارجي | 12 م / 8 م | `inner_belt_length_id` / `outer_belt_length_id` | نعم |
| قدرة ماتور السبلة | 1.5 حصان | `motor_power_id` | نعم |
| عدد مواتير السبلة | 1 لكل خط في النص | `manure_motor_count_id` | نعم |
| سعر العنبر USD | 130,560 | `total / exchange_rate / barns_count` | نعم |
| سعر العنبر EGP | 6,789,120 | `total / barns_count` | نعم |
| سعر الصرف | 52 ضمنيًا (6,789,120 ÷ 130,560) | `exchange_rate` | نعم |
| عدد العنابر | غير ظاهر كصف؛ المبلغ «للعنبر الواحد» | `barns_count` | نعم |
| مسافة الانتقال | 100 سم | **ليس** `mi_proposal.specs` (هناك 102 للتسمين) | ثابت قالب بياض جديد |
| ارتفاع البطارية | 3.20 م | ثابت قالب بياض. التسمين في الكونفج 3.40 | لا — ثابت منفصل |
| طول/عمق/ارتفاع القفص | 100 / 65 / 45 سم | عمق وارتفاع يطابقان الكونفج. الطول 100 ثابت بياض | جزئي |
| مساحة القفص | 6500 سم² | 100×65 ثابت | نعم كثابت |
| مساحة التسكين | 390 سم² | **لا تطابق** 6500÷10=650 | لا — سؤال مفتوح |
| منطقة التغذية | 6 سم | 100÷10=10 سم، والعينة تقول 6 | لا — سؤال مفتوح |
| أبعاد باب القفص | 60×65×45 | نص ثابت، مختلف عن طول القفص 100 | ثابت نصي |
| زنك / ضمان / ارتباط | 275 جم، 12 عام، 3 أيام | `pricing_snapshot.terms` من `mi_proposal` | نعم للتسمين؛ ننسخ القيم لكونفج البياض دون قراءة كونفج التسمين وقت الرسم إن أمكن تفادي الاقتران |
| نسب الدفع | 70 / 25 / 5 | غير موجودة في الداتا | لا — ثابت في كونفج البياض |
| نص 1٪ قطع غيار | داخل وصف البند | غير محسوب كسطر | نص ثابت، ليس خصمًا ولا بندًا |

### الجداول والحسابات

جدول المواصفات عمودان: قيمة | بيان. لا ضرب أسعار.

جدول المال أعمدته: Description | Unit | Taxed | Amount (USD) | Amount (EGP).

- صف واحد في العينة، ليس جدول كمية × سعر وحدة.
- Unit = «داجن» (نفس مصطلح التسمين).
- Taxed = الكلمة «VAT» وليست نسبة ظاهرة ولا مبلغ ضريبة منفصل.
- لا عمود خصم. `FinancialEngine` يدعم الخصم، والحاسبة تمرّر 0. قالب البياض لا يعرض خصمًا.
- المبلغ الظاهر هو إجمالي العنبر الواحد بالعملتين. معدل التحويل في العينة = 52 بالضبط.
- لا سطر subtotal / VAT / total كما في صفحة 10 للتسمين.
- 1٪ قطع الغيار جملة داخل الوصف، وليست `qty × price × 0.01` في الجدول.

الحساب المقترح للقالب (بدون تغيير محرك التسعير):

- EGP للعنبر = `total / barns_count`
- USD للعنبر = EGP للعنبر / `exchange_rate`
- لا خصم، ولا إعادة حساب VAT داخل القالب. الضريبة تبقى كما حفظها المحرك في `pricing_snapshot.financial`.

### الشروط والضمان والدفعات

| البند | النص | أرقام |
|---|---|---|
| العرض شامل | نقل، تركيب، تعليف + سايلو، سباكة شرب، سبلة آلية، لوحة وكابلات، مواتير تركي، زنك، ضمان حديد | زنك 275 جم/م² (الملف كتب م3 مرة؛ المعتمد في باقي النص م²)، ضمان 12 عام |
| الارتباط | ثلاثة أيام من تاريخ التقديم | 3 |
| الضمان | 12 شهر عيوب تصنيع من التركيب، و12 سنة صاج وسلك ضد الصدأ بشروط الغسيل | 12 و 12 |
| التركيب | السعر شامل تركيب ونقل. الإقامة على العميل | — |
| قطع الغيار | توفير 12 سنة. السعر حسب السوق | 12 |
| الدفعات | 70٪ عند التعاقد، 25٪ أثناء التركيب، 5٪ مع آخر سيارة | 100٪ |
| التطهير وخدمة ما بعد البيع | نصوص ثابتة | — |

فرق عن صفحة 10 الحالية: التسمين يقول «التركيب شاملاً إقامة العمالة». البياض يقول «الإقامة على العميل». لا ننسخ جملة التسمين.

### فروق جوهرية عن قالب التسمين

- التسمين: PDF مستورد صفحةً صفحة مع كتابة فوق الصفحات 2 و6 و9 و10. البياض: مستند كامل (غلاف، صور منتج، مواصفات، مال، ضمان، دفعات، تطهير، ما بعد البيع) ويُرسم من HTML، لا يُركب فوق `mi-proposal-template.pdf`.
- التسمين يعيد استخدام ثوابت القفص في `config/mi_proposal.php` (مسافة 102 سم، ارتفاع بطارية 3.40). البياض في الملف: 100 سم و3.20 م. ثوابت منفصلة حتى لا يتغير التسمين.
- صفحة 9 للتسمين فيها سيناريوهات وزن التسمين. البياض قطيع ثابت 10 طيور/قفص بلا جدول أوزان.
- البياض يضيف جمع البيض، دولاب البيض، وصور المنتج. هذه الصفحات غير موجودة كصفحات ديناميكية في التسمين.
- الوصف المالي للتسمين يذكر «تسمين» ولوح كنترول. البياض يذكر «بطاريات بياض + منظومة الجمع الآلي».
- تربية البياض (`layer_rearing`) نوع مشروع بياض، لكن `PoultryTechnicalCalculator` يرفض الحساب. القالب لا يصلح هذا.

---

## 4. الصور

التصنيف من موقع الرابط في الـ markdown فقط. لم تُفتح الملفات.

| ملفات | التصنيف | ثابت / متغير |
|---|---|---|
| `image23.jpg`، `image24.png` | ترويسة الصفحة (خلفية أو شعار؛ الاثنان في `header1.xml`) | ثابت على كل الصفحات |
| `image1.png`، `image2.png` | فن الغلاف قبل العنوان | ثابت |
| `image3.png` | صورة قسم أبعاد العش | ثابت (صورة موديل الكتالوج الحالي) |
| `image4.png`–`image13.png`، `image14.jpg` | صور منتج لأقسام السلك، الباب، الترولي، العلافة، المياه، الأرضية، السبلة، السير | ثابت. الربط الدقيق لكل صورة بقسم واحد غير محسوم من الـ markdown |
| `image15.png`، `image16.png` | جمع البيض / دولاب البيض | ثابت |
| `image17.jpg`، `image18.png`، `image19.jpg` | سايلو / بريمة (تجاور النص) | ثابت. السعة رقم يتغير، الصورة لا |
| `image20.jpg`، `image21.jpg` | لوحات الكهرباء | ثابت |
| `image22.png` | غير مذكور في النص | لا يُضمَّن حتى يُراجع |
| أختام | لا يوجد ختم في الـ markdown | لا ننقل ختم `company-seal` المستخدم في PDF القديم |

لا صورة تتغير حسب عرض السعر اليوم. التغيير حسب الموديل مؤجّل إلى أن يوجد أكثر من موديل بياض في الداتا. حتى ذلك الحين كل الصور أصول قالب.

الأحجام الحالية 0.5–2.7MB للصورة. 24 صورة خام تتجاوز 5MB بسهولة. انظر قسم الـ PDF.

---

## 5. الـ PDF

- المكتبة الحية: `mpdf/mpdf` مباشرة داخل `MiProposalPdfGenerator`.
- `carlos-meneses/laravel-mpdf` مستخدمة فقط في `PoultryQuotationPdfGenerator` (غير موصول بالراوت) وفي كارت المشاركة.
- العربية وRTL تعمل في التسمين: خط Cairo من `public/fonts` أو `storage/fonts`، `autoScriptToLang`، `autoLangToFont`، و`useOTL` + `useKashida` على Cairo.
- التوصية: البقاء على mPDF لقالب البياض. لا مكتبة جديدة. لا استيراد لقالب التسمين.

قيد الحجم:

- mPDF يضمّن JPEG وPNG بثبات. WebP غير معتمد كمسار PDF.
- النسخة المعتمدة للقالب: JPEG، أطول ضلع 1600px، جودة حوالي 70، هدف 80–150KB للصورة.
- 20 صورة مستخدمة × 150KB ≈ 3MB. مع الخط والصفحات يبقى الملف تحت 5MB.
- الأصول الخام تبقى في `docs/quotations/layers/media/`. المضغوط يذهب إلى `resources/quotations/layers/` ولا يُرفع الأصل إلى الـ PDF.
- صور الترويسة المتكررة تُرسم مرة في الـ header لا تُعاد كملف كامل في كل صفحة إن أمكن، حتى لا يتضاعف الحجم.

---

## 6. المعمارية

```
QuotationTemplate
  key(): string
  previewHtml(PoultryQuotation): string
  pdf(PoultryQuotation): Response

BroilerQuotationTemplate
  يفوّض إلى MiProposalPdfGenerator كما هو. لا منطق جديد.

LayersQuotationTemplate
  HTML من resources/views/poultry/layers/*
  وPDF عبر Mpdf بنفس إعداد Cairo.

QuotationTemplateResolver
  isLayer(project_type) ? Layers : Broiler
```

نقطة الربط الوحيدة في مرحلة 1: الاستدعاءان في `routes/web.php`. التسمين يمر من الـ Resolver إلى نفس المولّد. لا شرط داخل `MiProposalPdfGenerator`.

`quote_type_id` للبياض يُملأ عند الحفظ للتوثيق والتقارير. الـ Resolver لا يقرأه.

### تغييرات الداتا

لا جدول جديد.

- Seeder: صف `quote_type` / code `batteries_layers_eg` / label `بطاريات فقط بياض مصري` / value `batteries_only_layers_eg`. الإضافة لا تعطّل كود التسمين (`updateOrCreate` على الكود، وقائمة التعطيل في الـ seeder يجب أن تُبقي الاثنين معًا).
- لا migration إلا إذا احتجنا عمودًا لاحقًا. `quote_type_id` يكفي.
- لا backfill. عروض التسمين الحالية تبقى `quote_type_id = null` ويبقى PDFها كما هو.
- كونفج جديد `config/layers_quotation.php`: ثوابت القفص (100 سم، 3.20 م، 6500 سم²)، نصوص الأقسام، نسب 70/25/5، مدة 3 أيام، ومسار الصور المضغوطة. لا نكتب داخل `config/mi_proposal.php`.

### Field mapping

هو جدول القسم 3. صفحة المال تعرض إجمالي العنبر المحسوب، لا تعيد تسعير البنود.

### الصور

1. سكربت تحضير (مرحلة 2، ليس الآن) يقرأ `docs/quotations/layers/media/` ويكتب JPEG مضغوطًا في `resources/quotations/layers/`.
2. القالب يشير إلى النسخة المضغوطة فقط.
3. `image22.png` مؤجّل.

### المراحل

#### 1) البنية والـ Resolver + Tests

تُنشأ:

- `app/Contracts/Quotations/QuotationTemplate.php`
- `app/Services/Quotations/BroilerQuotationTemplate.php`
- `app/Services/Quotations/LayersQuotationTemplate.php` (واجهة فقط؛ PDF البياض في مرحلة 2)
- `app/Services/Quotations/QuotationTemplateResolver.php`
- `tests/Unit/QuotationTemplateResolverTest.php`

تُعدَّل:

- `app/Enums/PoultryProjectType.php` — إضافة `isLayer()` فقط.
- `database/seeders/LookupSeeder.php` — صف البياض مع الإبقاء على صف التسمين.
- `app/Models/PoultryQuotation.php` — عند الحفظ: إذا `isLayer()` و`quote_type_id` فارغ، عيّن lookup البياض. لا تلمس صف التسمين.
- `routes/web.php` — الاستدعاءان يمران على الـ Resolver.

اختبار الإلزام: نوع تسمين أو `null` يرجع `BroilerQuotationTemplate`، وهذا القالب يستدعي `MiProposalPdfGenerator` دون فرع بياض. أنواع `layer` و`layer_auto_collect` و`layer_rearing` ترجع قالب البياض.

#### 2) قالب البياض (Preview + PDF)

تُنشأ:

- `config/layers_quotation.php`
- `app/Services/Quotations/Layers/LayersQuoteData.php` (قراءة snapshot فقط)
- `resources/views/poultry/layers/` (غلاف، مواصفات مصوّرة، جدول فني، مال، شروط، تطهير، ما بعد البيع)
- `resources/quotations/layers/*.jpg` بعد ضغط الصور
- `tests/Unit/LayersQuoteDataTest.php` على أرقام 81 × 11.5 × 3.5 و4×4×10

تُستكمل `LayersQuotationTemplate` للرسم. لا تعديل على ملفات قسم «خط أحمر».

#### 3) كارت واتساب + Signed URL + Open Graph

موجود اليوم: رسالة `PoultryWelcomeWhatsApp` وPDF موقّع، وكارت `PricingCardImageGenerator` بدون صفحة OG.

تُنشأ:

- قالب كارت بياض (لا يستبدل شكل التسمين إن بقي الكارت مشتركًا؛ الكارت الجديد يُختار من الـ Resolver)
- route موقّع لصورة الكارت
- صفحة HTML عامة بوسوم Open Graph (عنوان، وصف، صورة الكارت) لنفس التوقيع

تُعدَّل لاحقًا نقطة توليد الكارت في `PoultryQuotationResource` و`ViewPoultryQuotation` بحيث تستدعي الـ Resolver. زر التسمين يبقى على المولّد الحالي إذا كان النوع تسمين.

#### 4) واجهة الحاسبة

- Segmented control تسمين/بياض يكتب نفس قيم `project_type` الحالية (`broiler` أو `layer`). لا قيم جديدة.
- جمع آلي وتربية بياض يبقيان في اختيار ثانوي حتى لا نضيع الأنواع الموجودة.
- الملخص الحي موجود في `HasLivePoultryPricing`. المرحلة 4 تعرض في الملخص حقول البياض (أقفاص، 10 طيور، طول فعال) عندما `isLayer()`.
- أي تعديل في `PoultryQuotationResource` يكون بالإضافة. خيارات التسمين ومسار `isBroiler()` تبقى.

### المخاطر

| خطر | التجنب |
|---|---|
| تغيير مخرج PDF التسمين | التسمين يفوّض للمولّد الحالي. اختبار Resolver يثبت الكلاس. لا تحرير لملفات الخط الأحمر. |
| backfill يكتب `quote_type_id` على عروض قديمة | ممنوع. الملء لعروض البياض الجديدة ذات العمود الفارغ فقط. |
| seeder يعطّل `batteries_broiler_eg` | قائمة الأكواد في `LookupSeeder` تضم الاثنين قبل `whereNotIn`. |
| صور خام تفجّر حجم PDF | JPEG ≤1600px في `resources/quotations/layers/` فقط. |
| ربط صورة خاطئة بقسم | عنقود image4–14 غير محسوم. مرحلة 2 تبدأ بصفحات بلا قص إبداعي، والربط يُقفل بسؤال مفتوح. |
| 390 سم² و6 سم لا تطابق 6500 و10 | لا نخترع معادلة. الثابت من الملف يُعرض كنص مواصفة معتمد بعد جواب السؤال، أو نعرض 6500÷10 إذا اعتُمد الحساب. |
| `layer_rearing` يختار قالب بياض والحساب يرمي استثناء | القالب لا يُصلح الحاسبة. الرسالة الحالية تبقى. سؤال مفتوح: هل القالب يظهر أصلًا قبل تفعيل الحساب. |
| جمل الضمان تختلف عن التسمين (الإقامة) | نصوص البياض في `config/layers_quotation.php` فقط. |
| تعديل الفورم يكسر الحساب الحي للتسمين | المرحلة 4 لا تغيّر مفاتيح الحقول ولا `pricesAs()`. |

---

## Decisions

عند التعارض مع الأقسام 2 و6، هذا القسم هو المعتمد. مسارات المرحلة 1: `App\Quotations` و`config/quotations.php`.

1. **مساحة التسكين والتغذية:** لا أرقام ثابتة. تُحسب من (عرض العين × عمقها ÷ عدد الطيور) و(عرض العين ÷ عدد الطيور). الأرقام 390 سم² و6 سم في المستند الأصلي محسوبة على عين عرضها 60 سم وليس 100 سم. ينتظر ذلك تأكيدًا فنيًا قبل المرحلة 2.
2. **VAT:** نفس منطق قالب التسمين: صافي ثم VAT ثم إجمالي في أسطر صريحة. ينتظر تأكيدًا ماليًا.
3. **الصور:** `config/quotations/layers.php` يحتوي على `section => [images]` بترتيب مبدئي قابل للمراجعة. عنقود image4–image14 غير محسوم.
4. **image22.png:** مستبعد من القالب، ومنقول إلى `docs/quotations/layers/media/_unused/`.
5. **layer_rearing:** مدعوم في الـ Resolver خلف `quotations.layer_rearing_enabled = false`. القيمة `false` ترمي استثناء domain واضحًا، لا خطأ 500 عامًا.
6. **دفعات 70/25/5:** مبالغ محسوبة بالعملتين مع النسبة، وفرق التقريب في الدفعة الأخيرة بحيث المجموع = الإجمالي بالضبط.
7. **قطع غيار 1٪:** بند وصفي. النسبة من `quotations.spare_parts_percent`. لا تُضاف للإجمالي.
8. **كارت واتساب:** لكل الأنواع. كل Template يوفّر `shareCardData()`.
9. **تجميد القالب:** يُحفظ `quote_type_id` عند إنشاء أي عرض جديد إذا كان فارغًا. أولوية الـ Resolver: `quote_type_id` أولًا، ثم `project_type` كـ fallback، ثم قالب التسمين كافتراضي آمن. لا يوجد backfill. عرض قديم بلا `quote_type_id` يتبع `project_type`.
