<?php

namespace App\Quotations\Layers;

class LayersDocument
{
    /** @param  array<string, mixed>  $data */
    public function present(array $data): array
    {
        $company = $this->company();
        $rate = isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : 0.0;
        $financial = is_array($data['financial'] ?? null) ? $data['financial'] : [];
        $subtotal = (float) ($financial['subtotal'] ?? 0);
        $vat = (float) ($financial['vat_amount'] ?? 0);
        $total = (float) ($financial['total'] ?? 0);

        return [
            'accent' => $company['accent'],
            'company' => $company,
            'client' => $this->text($data['client_name'] ?? null),
            'quote_number' => $this->text($data['quote_number'] ?? null),
            'project_type' => $this->text($data['project_type_label'] ?? null),
            'issued_at' => $this->text($data['issued_at'] ?? null),
            'validity_date' => $this->text($data['validity_date'] ?? null),
            'validity_days' => (int) ($data['validity_days'] ?? 0),
            'location' => $this->text($data['location'] ?? null),
            'length' => $this->measure($data['length'] ?? null, 'متر'),
            'width' => $this->measure($data['width'] ?? null, 'متر'),
            'height' => $this->measure($data['height'] ?? null, 'متر'),
            'figures' => [
                ['value' => $this->count($data['bird_count'] ?? null), 'label' => 'السعة بالطيور'],
                ['value' => $this->count($data['tiers'] ?? null), 'label' => 'عدد الأدوار'],
                ['value' => $this->money($total), 'label' => 'الإجمالي EGP'],
                ['value' => $this->text($data['supply_days'] ?? null), 'label' => 'مدة التوريد'],
            ],
            'images' => $this->images(),
            'blocks' => $this->blocks($data),
            'spec_groups' => $this->specGroups($data),
            'stocking' => $this->stockingView(is_array($data['stocking'] ?? null) ? $data['stocking'] : []),
            'offer' => [
                'description' => 'توريد بطاريات '.$this->text($data['project_type_label'] ?? null).' بالمواصفات المذكورة أعلاه + منظومة الجمع الآلي / توريد قطع غيار '.$this->count($data['spare_parts']['percent'] ?? null).' % من العنبر كخامات صاج وسلك.',
                'unit' => (string) config('quotations.layers.unit', 'داجن'),
                'taxed' => $vat > 0 ? 'VAT' : 'غير خاضع',
                'barn_usd' => $this->money($financial['barn_usd'] ?? null),
                'barn_egp' => $this->money($financial['barn_egp'] ?? null),
                'rows' => [
                    ['label' => 'صافي', 'usd' => $this->money($rate > 0 ? round($subtotal / $rate, 2) : null), 'egp' => $this->money($subtotal)],
                    ['label' => 'VAT', 'usd' => $this->money($rate > 0 ? round($vat / $rate, 2) : null), 'egp' => $this->money($vat)],
                    ['label' => 'إجمالي', 'usd' => $this->money($rate > 0 ? round($total / $rate, 2) : null), 'egp' => $this->money($total)],
                ],
            ],
            'includes' => $this->includes($data),
            'validity_line' => 'مدة الارتباط بالعرض ('.$this->daysWords((int) ($data['validity_days'] ?? 0)).') من تاريخ تقديم العرض',
            'warranty' => $this->warranty($data),
            'installation' => [
                'سعر البطاريات شامل التركيب والنقل.',
                'الإقامة (على العميل) شاملة توفير سكن ملائم لظروف العمل لأفراد التركيبات.',
            ],
            'spare_parts_notes' => [
                'تلتزم شركة MI بضمان توفير قطع الغيار لمدة '.$this->count(config('quotations.layers.spare_parts_years')).' سنة من تاريخ التصنيع.',
                'أسعار قطع الغيار على حسب الكميات وتاريخ التوريد وذلك لتغير أسعار السوق بشكل مستمر.',
            ],
            'payments' => $this->paymentRows(is_array($data['payments'] ?? null) ? $data['payments'] : []),
            'payment_intro' => 'يتم سداد المبلغ على '.$this->count(count($data['payments'] ?? [])).' دفعات مقسمة كالآتي:',
            'disinfection' => $this->disinfection(),
            'after_sales' => $this->afterSales(),
            'n_specs' => '16',
            'n_money' => '17',
            'n_includes' => '18',
            'n_warranty' => '19',
            'n_install' => '20',
            'n_spares' => '21',
            'n_payments' => '22',
            'n_clean' => '23',
            'n_service' => '24',
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function blocks(array $data): array
    {
        $spare = $this->count($data['spare_parts']['percent'] ?? null);
        $zinc = $this->count(config('quotations.layers.zinc_g_m2'));
        $motor = $this->text($data['motor_power'] ?? null);
        $motors = $this->text($data['manure_motor_count'] ?? null);
        $belts = $this->text($data['belts_per_line'] ?? null);
        $inner = $this->text($data['inner_belt'] ?? null);
        $outer = $this->text($data['outer_belt'] ?? null);
        $silo = $this->text($data['silo_capacity'] ?? null);

        return [
            $this->block('01', 'أبعاد العش', 'Cage Dimensions', 'nest_dimensions', [
                'المقاس يُؤخذ من بيانات العرض. إذا لم يتوفر عرض العين أو عمقها تُعرض القيمة بعلامة — دون افتراض رقم.',
            ]),
            $this->block('02', 'سلك شبك', 'Wire Partition', 'mesh_wire', [
                'يتم مراعاة المسافات بين الأسلاك للحصول على معدل التهوية المطلوبة ودرجات إضاءة مناسبة.',
                'يتم استخدام أسلاك مجلفنة قبل التصنيع '.$zinc.' جم زنك/م2 بتخانة 2.7 مم.',
            ]),
            $this->block('03', 'باب القفص', 'Cage Door', 'cage_door', [
                'يصنع باب القفص من أسلاك مجلفنة على الساخن بدرجة (275gm zinc/m2) بتخانة 5 مم.',
                'يتم مراعاة سهولة الفتح والغلق الأفقي على أسلاك مجلفنة بتخانة 3 مم.',
                'الطول 60 سم. العرض 65 سم. الارتفاع 45 سم.',
            ]),
            $this->block('04', 'ترولي العلف', 'Feed Trolley', 'feed_trolley', [
                'يتم التغذية بواسطة عربات خاصة بتخانة 1 مم من الصاج المجلفن على الساخن.',
                'تقوم بتوزيع العلف مع إمكانية التحكم بعيارات العلف لكل طابق.',
                'يتم تحركها آلياً بطول الحقل عن طريق ماتور 0.5 حصان لكل خط.',
            ]),
            $this->block('05', 'العلافات', 'Feed Trough', 'feeders', [
                'تصنع العلافات من ألواح من الصاج المجلفن على الساخن بتخانة 0.8 مم بتصميم يتناسب مع حركة عربات العلف بطول الحقل.',
                'يتم تصنيع حامل العلافة من الصاج المجلفن على الساخن بتخانة 2 مم.',
                'مدعم بالاسطمبات ويوضع على مسافة كل 60 سم (يتحمل أشخاص بوزن 120 كيلو).',
            ]),
            $this->block('06', 'خطوط المياه', 'Water Lines', 'water_lines', [
                'يتم إيصال المياه عن طريق مواسير مربعة (22mm×22mm) مصنعة من البولي بروبلين.',
                'يحتوي كل قفص على عدد 3 نبل ستانلس ستيل بالكامل 360 درجة.',
                'يوجد أسفل خط المياه V-CUP لرجوع الماء المتساقط أثناء الشرب.',
                'كل خط يتم التحكم فيه عن طريق وحدة منظمات أو سيفون على حسب طلب العميل.',
            ]),
            $this->block('07', 'أرضيات', 'Floor', 'floors', [
                'يتم تصنيع الأرضيات من السلك المجلفن على الساخن (275gm zinc/m2) بميل 7 درجات لسهولة خروج البيض دون كسره.',
            ]),
            $this->block('08', 'دولاب السبلة', 'Manure Mechanism', 'manure_cabinet', [
                'سيور السبلة لكل طابق بتخانة 1 مم، بعرض 118 سم.',
                'عدد '.$motors.' بقوة '.$motor.' لكل خط بطاريات لتشغيل '.$belts.'.',
                'عمل مساحات مدمجة بالفايبر لسيور السبلة وتصنع من الاستانلس ستيل 304.',
            ]),
            $this->block('09', 'السير العرضي وسير التحميل', 'Cross Conveyor And Loading Conveyor', 'cross_belt', [
                'يتم توريد سير داخلي بطول '.$inner.' وسير خارجي بطول '.$outer.'.',
                'يتم تصنيع شاسيهاته من علب 4×4 بسمك 2 مم معزول إيبوكسي ومكسو بطبقة إليكتروستاتيك وبه حوامل بكر بلاستيك للحفظ من التآكل والصدأ.',
                'يصنع السير من شيفرون كاوتش خامة شاقة 2 تيلة أملس أسود اللون.',
            ]),
            $this->block('10', 'دولاب البيض', 'Eggs Collection Rack', 'egg_collection', [
                'تتم عملية جمع البيض عن طريق سيور بعرض 10 سم مصنعة من البولي بروبلين المنسوج ممتدة طولياً بامتداد البطارية وصولاً إلى دولاب جمع البيض (أسانسير) يعمل بشكل رأسي.',
                'كل هذه العملية تتم بشكل آلي بالكامل بقوة '.$motor.' لكل خط صناعة تركي بسرعة 9 لفات/الدقيقة.',
                'تشمل كل وحدة على رف جمع لكل خط داخل العنبر.',
            ]),
            $this->block('11', 'سايلو العلف', 'Feed Storage', 'silo', [
                'يصنع سايلو العلف من الصاج المجلفن على الساخن بتخانة 2 مم.',
                'سعة السايلو '.$silo.'.',
                'يتم التغذية منه آلياً عن طريق بريمة سوستة مصنعة من الصلب لنقل العلف لكل عربة بالحقل.',
            ]),
            $this->block('12', 'بريمة العلف', 'Screw Feeder', null, [
                'تصنع بريمة العلف عن طريق ماسورة حديد 4 بوصة تخانة 3 مللي مغطاة بطبقة من الإيبوكسي عازل للرطوبة بعد التصنيع وتكون بطول 10 متر.',
                'يتم شد سوستة صلب 4 بوصة داخل الماسورة بين كتلة الماتور وكرسي بلي.',
                'تحمل البريمة ماتور تركي بقدرة 1 حصان بتحكم حركي كوبلن أمامي وبه خزان أمامي سعة 100 كيلو علف.',
                'التحكم في البريمة يكون عن طريق مفتاح قاطع خارج العنبر.',
            ]),
            $this->block('13', 'لوحات الكهرباء', 'Electric Panels', 'electrical_panels', [
                'نقدم لعملائنا مجموعة من لوحات الكهرباء المصممة على أيدي مهندسين محترفين وعلى قدر كبير من الخبرة والكفاءة.',
                'هذه اللوحات SEMINS تتيح للمشغل التحكم بشكل كامل في تشغيل البطاريات ونظام كسح السبلة بشكل أوتوماتيكي بالكامل بما يناسب السادة العملاء حسب رغباتهم.',
            ]),
            $this->block('14', 'منظومة التحكم في السبلة', 'Manure Control', null, [
                'تتم هذه العملية بشكل أوتوماتيكي بالكامل عن طريق سيور طولية ممتدة تحت كل طابق من طوابق البطارية لتوصيلها إلى سير عرضي ومنه إلى سير التحميل لتعبئة الشاحنة أو الحاوية ونقل السبلة خارج العنبر بطريقة نظيفة وآمنة.',
                'يتم التحكم بها عن طريق ريموت كنترول يسمح لمسافة 80 متر داخل الحقل.',
            ]),
            $this->block('15', 'منظومة التغذية', 'Feeding System', null, [
                'هي منظومة تدار بشكل أوتوماتيكي كامل حيث يتم توزيع العلف عن طريق عربات توزيع على كامل طول الخط يميناً ويساراً في جميع طوابق البطاريات.',
                'يتم التحكم بتوقيتات زمنية مختلفة للتعليف وجميع حساسات الفصل تم تصنيعها من مكونات ألمانية.',
                'نسبة قطع الغيار الوصفية داخل البند المالي '.$spare.' % ولا تُضاف إلى الإجمالي.',
            ]),
        ];
    }

    /** @param  list<string>  $paragraphs */
    private function block(string $index, string $title, string $titleEn, ?string $images, array $paragraphs): array
    {
        return [
            'index' => $index,
            'title' => $title,
            'title_en' => $titleEn,
            'images' => $images,
            'paragraphs' => $paragraphs,
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function specGroups(array $data): array
    {
        return [
            [
                'title' => 'المواصفات الفنية للبطاريات',
                'rows' => [
                    ['label' => 'الطول الفعال للبطارية', 'value' => $this->measure($data['effective_length'] ?? null, 'متر')],
                    ['label' => 'عدد الأدوار', 'value' => $this->count($data['tiers'] ?? null)],
                    ['label' => 'عدد الخطوط', 'value' => $this->count($data['lines'] ?? null)],
                    ['label' => 'مسافة الانتقال بين الخطوط', 'value' => $this->measure($data['aisle_cm'] ?? null, 'سم')],
                    ['label' => 'ارتفاع البطارية', 'value' => $this->measure($data['battery_height_m'] ?? null, 'متر')],
                    ['label' => 'عدد الأقفاص بالجهة الواحدة', 'value' => $this->count($data['nests_one_side'] ?? null, 'قفص')],
                    ['label' => 'عدد الأقفاص بالعنبر', 'value' => $this->count($data['total_nests'] ?? null, 'قفص')],
                ],
            ],
            [
                'title' => 'المواصفات الفنية للقفص',
                'rows' => [
                    ['label' => 'طول القفص', 'value' => $this->measure($data['stocking']['width_cm'] ?? null, 'سم')],
                    ['label' => 'عمق القفص', 'value' => $this->measure($data['stocking']['depth_cm'] ?? null, 'سم')],
                    ['label' => 'ارتفاع القفص', 'value' => $this->measure($data['cage_height_cm'] ?? null, 'سم')],
                    ['label' => 'مساحة القفص', 'value' => $this->measure($data['cage_area_cm2'] ?? null, 'سم²')],
                    ['label' => 'عدد القطيع في القفص الواحد', 'value' => $this->count($data['birds_per_nest'] ?? null, 'طائر')],
                    ['label' => 'عدد القطيع بالعنبر', 'value' => $this->count($data['bird_count'] ?? null, 'طائر')],
                ],
            ],
        ];
    }

    /** @param  array<string, mixed>  $stocking */
    private function stockingView(array $stocking): array
    {
        $width = $stocking['width_cm'] ?? null;
        $depth = $stocking['depth_cm'] ?? null;
        $birds = $stocking['birds'] ?? null;

        return [
            'area' => $this->measure($stocking['area_cm2'] ?? null, 'سم²'),
            'feeding' => $this->measure($stocking['feeding_cm'] ?? null, 'سم'),
            'area_equation' => $this->part($width).' × '.$this->part($depth).' ÷ '.$this->part($birds),
            'feeding_equation' => $this->part($width).' ÷ '.$this->part($birds),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function includes(array $data): array
    {
        $zinc = $this->count(config('quotations.layers.zinc_g_m2'));
        $years = $this->count(config('quotations.layers.warranty_years'));

        return [
            'النقل.',
            'التركيب.',
            'منظومة تعليف أوتوماتيك مع سايلو.',
            'أعمال السباكة لمنظومة الشرب.',
            'منظومة خروج الفضلات وتحميلها آلي.',
            'لوحة تحكم للبطاريات شاملة الكابلات وحامل الكابلات.',
            'مواتير الكهرباء الخاصة بتشغيل البطارية أوتوماتيك (جديدة تركي).',
            'معدل الطلاء المجلفن '.$zinc.' جم زنك/م2.',
            'ضمان '.$years.' عام للحديد وجلفنة الصاج.',
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function warranty(array $data): array
    {
        $months = $this->count(config('quotations.layers.warranty_months'));
        $years = $this->count(config('quotations.layers.warranty_years'));

        return [
            'يضمن البائع كل المنتجات ضد عيوب التصنيع لمدة '.$months.' شهر من تاريخ التركيب.',
            'قيمة الأجزاء المستبدلة يلتزم بها البائع طوال فترة الضمان وذلك بعد استلام الأجزاء المعيبة.',
            'الشركة غير مسئولة عن الأخطاء الناتجة من سوء الاستخدام أو الحوادث أو الكوارث الطبيعية لا قدر الله.',
            'يعتبر الضمان لاغياً من تلقاء نفسه في حالة اكتشاف أي تعديل أو تغيير على البطاريات المباعة من قبل المشتري دون الرجوع إلى البائع وتأكيد ذلك بإذن كتابي من البائع.',
            'يعتبر الضمان لاغياً من تلقاء نفسه في حالة تركيب البطاريات أو إصلاحها من قبل شركة أخرى.',
            'ضمان '.$years.' سنة على الصاج والسلك ضد الصدأ والتآكل ما لم يتم غسله بمواد كيميائية أو مياه مالحة وعند حدوث ذلك يعتبر العقد لاغياً.',
        ];
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function paymentRows(array $lines): array
    {
        $rows = [];
        $egpCents = 0;
        $usdCents = 0;
        $percentSum = 0;
        $hasUsd = true;

        foreach ($lines as $line) {
            $egp = (float) ($line['egp'] ?? 0);
            $usd = $line['usd'] ?? null;
            $percentSum += (int) ($line['percent'] ?? 0);
            $egpCents += (int) round($egp * 100);
            if ($usd === null) {
                $hasUsd = false;
            } else {
                $usdCents += (int) round(((float) $usd) * 100);
            }
            $rows[] = [
                'percent' => $this->count($line['percent'] ?? null).'%',
                'label' => (string) ($line['label'] ?? ''),
                'usd' => $this->money($usd),
                'egp' => $this->money($egp),
            ];
        }

        return [
            'rows' => $rows,
            'total' => [
                'percent' => $this->count($percentSum).'%',
                'usd' => $hasUsd ? $this->money($usdCents / 100) : '—',
                'egp' => $this->money($egpCents / 100),
            ],
        ];
    }

    /** @return array<string, list<string>> */
    private function disinfection(): array
    {
        return [
            'goals' => [
                'الحفاظ على كفاءة البطاريات ومنع تراكم الأوساخ والبكتيريا.',
                'حماية البطاريات المعدنية من الصدأ والتآكل لضمان عمر افتراضي أطول.',
                'توفير بيئة نظيفة وصحية للدواجن لتحسين الإنتاجية.',
            ],
            'recommendations' => [
                'دورية التنظيف: إجراء التنظيف والتطهير الكامل بعد كل دورة إنتاج.',
                'الصيانة الدورية: فحص البطاريات والأسطح لضمان عدم وجود تآكل أو تلف.',
                'هذا الدليل يعكس أعلى معايير السلامة والكفاءة لضمان بيئة نظيفة وآمنة في مزارع الدواجن.',
            ],
            'steps' => [
                'التفريغ والتهيئة (اليوم الأول): تفريغ العنبر من الدواجن والمعدات القابلة للإزالة. إزالة الفضلات والمواد العضوية يدوياً أو باستخدام أجهزة شفط.',
                'التنظيف الأولي (اليوم الثاني): شطف البطاريات والأسطح بالماء باستخدام خرطوم ضغط منخفض لإزالة الأوساخ العالقة. تشغيل الشفاطات للتخلص من الرطوبة الزائدة.',
                'استخدام منظف رغوي (Foam Cleaner): رش مادة تنظيف فوم متعادل (مثل naturale أو FCD 2000 أو H2O2) على جميع الأسطح. ترك المادة لمدة 15-20 دقيقة لضمان تفكيك الأوساخ والشحوم، واستخدام مياه الأكسجين لتنظيف خطوط النبل، ويراعى ترك المادة داخل المواسير لمدة 12 ساعة ثم ضخها بكمية مياه كافية لطرد البكتيريا والطحالب.',
                'الشطف النهائي (اليوم الثالث): شطف البطاريات والأسطح جيداً بالماء لإزالة بقايا المنظف.',
                'التطهير (اليوم الرابع): استخدام مادة مطهرة معتمدة (مثل مطهر يود أو H2O2 أو حمض البيراسيتيك) لتطهير الأسطح. ترك المادة للتفاعل لمدة 10-15 دقيقة، ثم رش مطهر فوركن إس.',
                'التجفيف (اليوم الخامس): تشغيل الشفاطات لضمان جفاف العنبر بالكامل لتقليل فرص نمو البكتيريا. ثم تبخير العنبر بأقراص فورمالين.',
            ],
            'notes' => [
                'يجب ارتداء معدات السلامة الشخصية أثناء التنظيف (القفازات، الأقنعة).',
                'تجنب استخدام المواد التي قد تسبب تآكل البطاريات المعدنية مثل الكلور بتركيزات عالية.',
                'تخزين مواد التنظيف والتطهير في أماكن باردة وجافة بعيداً عن متناول الأطفال.',
            ],
        ];
    }

    /** @return list<string> */
    private function afterSales(): array
    {
        return [
            'توافر مخزون كامل من قطع الغيار المستهلكة وغير المستهلكة في مقر الشركة في دمياط.',
            'يمكن للعميل إرسال فني التشغيل لديه للتدرب على التشغيل والصيانة وإدارة المشروع بالكامل في شركة MI بأرض المصنع بدمياط.',
            'يمكن للعميل إرسال فني التشغيل لديه للتدرب على التشغيل والصيانة وإدارة المزرعة بالكامل أثناء أعمال التركيبات والتدريب على يد أفضل المهندسين لحين الانتهاء من تركيبات العنبر.',
            'يحصل الفني على تدريب آخر عند إتمام تركيب العنبر من قبل أفضل التقنيين.',
            'لدى شركتنا فريق متكامل من المهندسين والتقنيين لخدمة ما بعد البيع.',
            'يمكنك طلب الدعم فوراً عند حدوث الأعطال عن طريق الجروبات الخاصة بمتابعة العملاء على WeChat أو WhatsApp. يتم إضافة كل من: المالك، المهندس المسؤول، الفني، ومدير شركة MI، وفريق خدمة ما بعد البيع.',
            'يتم فرز مهندس صيانة لكل خمس عنابر في منطقة واحدة.',
            'نتميز بسرعة الاستجابة لأي استفسار وسرعة الوصول للموقع حيث إننا متوفرون 24 ساعة / 7 أيام.',
            'نوفر تقنيين من شركة MI كل 3 أشهر للصيانة الدورية لعنابر عملائنا.',
            'تواجد الخبراء باستمرار وذلك تزامناً مع التركيبات التي تتم في أي مكان.',
            'يتم تقييم فريق عمل خدمة ما بعد البيع عن طريق التواصل مع العميل وقياس مدى رضائه عن الخدمة المقدمة له.',
        ];
    }

    /** @return array<string, list<array{path: string, w: int, h: int}>> */
    private function images(): array
    {
        $map = config('quotations.layers.images', []);
        $directory = resource_path('quotations/layers');
        $images = [];

        foreach ($map as $section => $files) {
            if (! is_array($files)) {
                continue;
            }
            $images[$section] = [];
            foreach ($files as $file) {
                $path = $directory.DIRECTORY_SEPARATOR.$file;
                if (! is_file($path)) {
                    continue;
                }
                $size = @getimagesize($path);
                $sourceWidth = (int) ($size[0] ?? 4);
                $sourceHeight = (int) ($size[1] ?? 3);
                $width = $section === 'cover' ? 210 : ($section === 'header' ? 28 : 82);
                $height = max(8, (int) round($width * ($sourceHeight / max(1, $sourceWidth))));
                if ($section !== 'cover' && $section !== 'header') {
                    $height = min($height, 52);
                }
                $images[$section][] = [
                    'path' => str_replace('\\', '/', $path),
                    'w' => $width,
                    'h' => $height,
                ];
            }
        }

        return $images;
    }

    /** @return array{name: string, accent: string, address: string, phone: string, email: string} */
    private function company(): array
    {
        $phones = $this->setting('contact.phones', ['+201026253004']);
        $phone = is_array($phones) ? implode(' | ', $phones) : (string) $phones;

        return [
            'name' => (string) $this->setting('company.name_ar', 'إم آي للصناعات المعدنية'),
            'accent' => (string) $this->setting('branding.primary_color', '#C00000'),
            'address' => (string) $this->setting('contact.address_ar', 'طريق رأس البر القديم - السنانية - دمياط'),
            'phone' => $phone,
            'email' => (string) $this->setting('contact.email', 'mi.cnc.factory@gmail.com'),
        ];
    }

    private function setting(string $key, mixed $default): mixed
    {
        try {
            return settings($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    private function text(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : '—';
    }

    private function measure(mixed $value, string $unit): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        return $this->number((float) $value).' '.$unit;
    }

    private function count(mixed $value, string $suffix = ''): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        $number = $this->number((float) $value);

        return $suffix !== '' ? $number.' '.$suffix : $number;
    }

    private function money(mixed $value): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        return $this->number((float) $value);
    }

    private function number(float $value): string
    {
        $decimals = abs($value - round($value)) < 0.001 ? 0 : 2;

        return number_format($value, $decimals, '.', ',');
    }

    private function part(mixed $value): string
    {
        return is_numeric($value) ? $this->number((float) $value) : '—';
    }

    private function daysWords(int $days): string
    {
        return match ($days) {
            1 => 'يوم واحد',
            2 => 'يومين',
            3 => 'ثلاثة أيام',
            default => $this->number($days).' أيام',
        };
    }
}
