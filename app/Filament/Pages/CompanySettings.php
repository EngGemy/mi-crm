<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'إعدادات الشركة';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.company-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.update') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('settings.update') ?? false;
    }

    protected static ?string $title = 'إعدادات الشركة';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->nestedSettingsState());
    }

    /**
     * DB keys are flat (company.name_ar); Filament fields are nested under statePath data.
     */
    protected function nestedSettingsState(): array
    {
        $nested = [];
        foreach (settings()->all() as $dotKey => $value) {
            if (! str_contains($dotKey, '.')) {
                continue;
            }
            [$group, $field] = explode('.', $dotKey, 2);
            $nested[$group][$field] = $value;
        }

        return $nested;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('change_reason')
                    ->label('سبب التعديل (اختياري)')
                    ->placeholder('مثال: تحديث بيانات الشركة بعد الترخيص الجديد')
                    ->columnSpanFull(),

                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('بيانات الشركة')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('الهوية')->schema([
                                    TextInput::make('company.name_ar')
                                        ->label('اسم الشركة بالعربية')
                                        ->required(),
                                    TextInput::make('company.name_en')
                                        ->label('اسم الشركة بالإنجليزية')
                                        ->required(),
                                    TextInput::make('company.tagline_ar')
                                        ->label('الشعار التسويقي بالعربية'),
                                    TextInput::make('company.tagline_en')
                                        ->label('الشعار التسويقي بالإنجليزية'),
                                    TextInput::make('company.owner_name_ar')
                                        ->label('اسم المالك / المدير'),
                                    TextInput::make('company.owner_title_ar')
                                        ->label('الصفة'),
                                ])->columns(2),

                                Section::make('نبذة')->schema([
                                    Textarea::make('company.about_ar')
                                        ->label('نبذة بالعربية')
                                        ->rows(4),
                                    Textarea::make('company.about_en')
                                        ->label('About in English')
                                        ->rows(4),
                                ]),
                            ]),

                        Tabs\Tab::make('الاتصال')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('العنوان')->schema([
                                    Textarea::make('contact.address_ar')
                                        ->label('العنوان بالعربية')
                                        ->required()
                                        ->rows(2),
                                    Textarea::make('contact.address_en')
                                        ->label('Address in English')
                                        ->rows(2),
                                    TextInput::make('contact.city')->label('المدينة'),
                                    TextInput::make('contact.country')->label('الدولة'),
                                ])->columns(2),

                                Section::make('وسائل الاتصال')->schema([
                                    TagsInput::make('contact.phones')
                                        ->label('أرقام التليفون')
                                        ->placeholder('أضف رقم واضغط Enter'),
                                    TextInput::make('contact.whatsapp')
                                        ->label('رقم الواتساب'),
                                    TextInput::make('contact.email')
                                        ->label('البريد الرئيسي')
                                        ->email()
                                        ->required(),
                                    TextInput::make('contact.email_secondary')
                                        ->label('بريد ثانوي')
                                        ->email(),
                                    TextInput::make('contact.website')
                                        ->label('الموقع الإلكتروني'),
                                ])->columns(2),
                            ]),

                        Tabs\Tab::make('قانوني')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('المستندات الرسمية')->schema([
                                    TextInput::make('legal.tax_number')
                                        ->label('الرقم الضريبي')
                                        ->required(),
                                    TextInput::make('legal.commercial_register')
                                        ->label('السجل التجاري')
                                        ->required(),
                                    TextInput::make('legal.license_number')
                                        ->label('رقم الترخيص'),
                                    TextInput::make('legal.default_vat_percentage')
                                        ->label('نسبة الضريبة %')
                                        ->numeric()
                                        ->suffix('%'),
                                ])->columns(2),
                            ]),

                        Tabs\Tab::make('الهوية البصرية')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make('الألوان')->schema([
                                    ColorPicker::make('branding.primary_color')
                                        ->label('اللون الأساسي'),
                                    ColorPicker::make('branding.secondary_color')
                                        ->label('اللون الثانوي'),
                                    ColorPicker::make('branding.accent_color')
                                        ->label('لون التمييز'),
                                ])->columns(3),

                                Section::make('الشعارات')->schema([
                                    FileUpload::make('branding.logo_main')
                                        ->label('الشعار الرئيسي')
                                        ->image()
                                        ->disk('public')
                                        ->directory('brand'),
                                    FileUpload::make('branding.logo_header')
                                        ->label('شعار الهيدر')
                                        ->image()
                                        ->disk('public')
                                        ->directory('brand'),
                                    FileUpload::make('branding.logo_footer')
                                        ->label('شعار الفوتر')
                                        ->image()
                                        ->disk('public')
                                        ->directory('brand'),
                                    FileUpload::make('branding.qr_code')
                                        ->label('QR Code')
                                        ->image()
                                        ->disk('public')
                                        ->directory('brand'),
                                ])->columns(2),
                            ]),

                        Tabs\Tab::make('الضريبة')
                            ->icon('heroicon-o-receipt-percent')
                            ->schema([
                                Section::make('نسب ضريبة القيمة المضافة')->schema([
                                    TextInput::make('tax.vat_rate_egypt')
                                        ->label('نسبة الضريبة - مصر (%)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.01)
                                        ->suffix('%')
                                        ->required(),
                                    TextInput::make('tax.vat_rate_ksa')
                                        ->label('نسبة الضريبة - السعودية (%)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.01)
                                        ->suffix('%')
                                        ->required(),
                                    Select::make('tax.default_vat_region')
                                        ->label('المنطقة الضريبية الافتراضية')
                                        ->options([
                                            'egypt' => 'مصر (14%)',
                                            'ksa' => 'السعودية (15%)',
                                            'none' => 'بدون ضريبة',
                                        ])
                                        ->native(false)
                                        ->required(),
                                ])->columns(3),
                            ]),

                        Tabs\Tab::make('المالية')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('إعدادات مالية عامة')->schema([
                                    TextInput::make('finance.default_discount_percentage')
                                        ->label('نسبة الخصم الافتراضية (%)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.01)
                                        ->suffix('%'),
                                    TextInput::make('finance.default_exchange_rate')
                                        ->label('سعر صرف الجنيه / الدولار')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('EGP'),
                                ])->columns(2),

                                Section::make('جدول الدفعات الافتراضي')
                                    ->description('مصفوفة JSON: [{"description":"...","percentage":70,"milestone_code":"CONTRACT_SIGN"}, ...]')
                                    ->schema([
                                        Textarea::make('finance.payment_schedule')
                                            ->label('جدول الدفعات (JSON)')
                                            ->helperText('مجموع النسب يجب أن يساوي 100')
                                            ->rows(6)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('تسعير الدواجن')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Section::make('الخزانات والبنود الثابتة')
                                    ->description('بند الخزانات يُحسب كمقطوعة ثابتة (لوت واحد) ويُضاف تلقائياً في المشروع الكامل ونطاق الإنشاءات.')
                                    ->schema([
                                        TextInput::make('poultry_pricing.tanks_fixed_cost')
                                            ->label('تكلفة الخزانات الثابتة (جنيه)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(400000)
                                            ->required()
                                            ->suffix('EGP'),
                                        Toggle::make('poultry_pricing.include_tanks_default')
                                            ->label('تضمين الخزانات في التسعير')
                                            ->default(true)
                                            ->helperText('عطّل هذا الخيار لاستبعاد بند الخزانات من العروض الجديدة'),
                                        TextInput::make('poultry_pricing.control_fixed_cost')
                                            ->label('لوحة التحكم (ثابت)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->suffix('EGP'),
                                        TextInput::make('poultry_pricing.price_per_bird')
                                            ->label('سعر الطائر (بطاريات)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->suffix('EGP')
                                            ->helperText('سعر ثابت (flat) مستقل عن الوزن المعروض في الحاسبة — يُحسب على أساس الوزن المرجعي أدناه'),
                                        TextInput::make('poultry_pricing.price_per_bird_reference_weight_kg')
                                            ->label('الوزن المرجعي لسعر الطائر (كجم)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.001)
                                            ->default(1.100)
                                            ->suffix('كجم'),
                                        TextInput::make('poultry_pricing.fan_capacity_kg')
                                            ->label('سعة المروحة (كجم طيور حية)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(5000)
                                            ->helperText('معادلة الشفاطات: ceil(عدد الطيور × أقصى وزن ÷ هذه القيمة)'),
                                        TextInput::make('poultry_pricing.layer_max_bird_weight_kg')
                                            ->label('أقصى وزن طائر (بياض — للتهوية)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.1)
                                            ->default(1.7)
                                            ->suffix('كجم'),
                                        TextInput::make('poultry_pricing.egp_to_usd_rate')
                                            ->label('سعر صرف الجنيه → دولار')
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->helperText('للعرض التقريبي: المبلغ بالدولار = المبلغ ÷ السعر'),
                                        Textarea::make('poultry_pricing.heater_lot_prices')
                                            ->label('أسعار الدفايات (JSON)')
                                            ->helperText('مثال: {"3":45000,"4":55000,"5":65000,"6":75000,"8":95000}')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('الافتراضيات')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make('عام')->schema([
                                    Select::make('defaults.currency')
                                        ->label('العملة الافتراضية')
                                        ->options([
                                            'EGP' => 'جنيه مصري',
                                            'USD' => 'دولار أمريكي',
                                            'SAR' => 'ريال سعودي',
                                            'AED' => 'درهم إماراتي',
                                        ]),
                                    Select::make('defaults.language')
                                        ->label('اللغة الافتراضية')
                                        ->options([
                                            'ar' => 'عربي',
                                            'en' => 'إنجليزي',
                                            'both' => 'الاثنين',
                                        ]),
                                ])->columns(2),

                                Section::make('العقود والعروض')->schema([
                                    TextInput::make('defaults.quotation_validity_days')
                                        ->label('صلاحية العرض (يوم)')
                                        ->numeric(),
                                    TextInput::make('defaults.manufacturing_days')
                                        ->label('مدة التصنيع (يوم)')
                                        ->numeric(),
                                    TextInput::make('defaults.warranty_months')
                                        ->label('الضمان (شهر)')
                                        ->numeric(),
                                    TextInput::make('defaults.warranty_years_steel')
                                        ->label('ضمان الصاج (سنة)')
                                        ->numeric(),
                                    TextInput::make('defaults.payment_advance_percentage')
                                        ->label('الدفعة المقدمة %')
                                        ->numeric(),
                                    TextInput::make('defaults.penalty_amount_per_day')
                                        ->label('الشرط الجزائي اليومي')
                                        ->numeric(),
                                ])->columns(3),
                            ]),

                        Tabs\Tab::make('PDF')
                            ->icon('heroicon-o-document-arrow-down')
                            ->schema([
                                Section::make('إعدادات PDF')->schema([
                                    Toggle::make('pdf.show_watermark')
                                        ->label('إظهار العلامة المائية'),
                                    Toggle::make('pdf.show_qr_code')
                                        ->label('إظهار QR Code'),
                                    TextInput::make('pdf.copyright_text_ar')
                                        ->label('نص حقوق النشر')
                                        ->columnSpanFull(),
                                ])->columns(2),

                                Section::make('دليل التطهير (التعقيم)')->schema([
                                    TextInput::make('pdf.disinfection_title')
                                        ->label('العنوان الرئيسي')
                                        ->columnSpanFull(),
                                    TextInput::make('pdf.disinfection_subtitle')
                                        ->label('العنوان الفرعي')
                                        ->columnSpanFull(),
                                    Textarea::make('pdf.disinfection_steps')
                                        ->label('خطوات التطهير (JSON)')
                                        ->helperText('صيغة JSON: [{"title":"...","desc":"..."}, ...]')
                                        ->rows(6)
                                        ->columnSpanFull(),
                                    TextInput::make('pdf.disinfection_warning_title')
                                        ->label('عنوان التنبيه'),
                                    Textarea::make('pdf.disinfection_warning_text')
                                        ->label('نص التنبيه')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $reason = $state['change_reason'] ?? null;
        unset($state['change_reason']);

        $service = settings();
        $allCurrent = $service->all();
        $changes = 0;
        $errors = [];

        foreach ($state as $group => $fields) {
            if (! is_array($fields)) {
                continue;
            }
            foreach ($fields as $field => $value) {
                $key = "{$group}.{$field}";
                $current = $allCurrent[$key] ?? null;

                // Normalise for comparison
                $normalisedValue = $this->normaliseValue($value);
                $normalisedCurrent = $this->normaliseValue($current);

                if ($normalisedValue === $normalisedCurrent) {
                    continue;
                }

                try {
                    $service->set($key, $value, Auth::id(), $reason);
                    $changes++;
                } catch (\Throwable $e) {
                    $errors[] = "{$key}: ".$e->getMessage();
                }
            }
        }

        if (! empty($errors)) {
            Notification::make()
                ->title('حصل خطأ أثناء حفظ بعض الإعدادات')
                ->body(implode("\n", array_slice($errors, 0, 3)))
                ->danger()
                ->send();

            return;
        }

        if ($changes === 0) {
            Notification::make()
                ->title('لم يتم تغيير أي إعداد')
                ->info()
                ->send();

            return;
        }

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->body("تم تسجيل {$changes} تغيير في سجل التغييرات")
            ->success()
            ->send();

    }

    protected function normaliseValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
