<?php

namespace App\Filament\Resources;

use App\Enums\PoultryPricingScope;
use App\Filament\Concerns\HasLivePoultryPricing;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\QuotationResource\Pages\CreateQuotation;
use App\Filament\Resources\QuotationResource\Pages\EditQuotation;
use App\Filament\Resources\QuotationResource\Pages\ListQuotations;
use App\Filament\Resources\QuotationResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\QuotationResource\RelationManagers\SectionAttachmentsRelationManager;
use App\Filament\Resources\QuotationResource\RelationManagers\TermAttachmentsRelationManager;
use App\Mail\QuotationMail;
use App\Models\ChangeLog;
use App\Models\ContractType;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationSection;
use App\Models\QuotationTerm;
use App\Models\QuotationType;
use App\Services\QuotationCalculator;
use App\Services\QuotationGenerator;
use App\Services\QuotationSharingService;
use App\Services\QuotationToContractConverter;
use App\Support\BroilerWeightReference;
use App\Support\HeaterOptions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class QuotationResource extends Resource
{
    use HasLivePoultryPricing;

    protected static ?string $model = Quotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'عروض الأسعار';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user && $user->can('quotations.view_own') && ! $user->can('quotations.view_any')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('quotations.view_any') || $user->can('quotations.view_own'));
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('quotations.view_any') || $user->can('quotations.view_own'));
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('quotations.create') ?? false;
    }

    protected static ?string $navigationLabel = 'عروض الأسعار';

    protected static ?string $modelLabel = 'عرض سعر';

    protected static ?string $pluralModelLabel = 'عروض الأسعار';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([

                // ====================================
                // Step 1: البيانات الأساسية
                // ====================================
                Forms\Components\Wizard\Step::make('البيانات الأساسية')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Section::make('معلومات العرض')
                            ->schema([
                                Forms\Components\TextInput::make('quotation_number')
                                    ->label('رقم العرض')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visibleOn('edit'),

                                Forms\Components\Select::make('customer_id')
                                    ->label('العميل')
                                    ->relationship('customer', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (Customer $r) => "{$r->name} ({$r->code})")
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->label('الاسم')->required(),
                                        Forms\Components\TextInput::make('national_id')->label('رقم الهوية'),
                                        Forms\Components\TextInput::make('phone')->label('الموبايل')->required(),
                                        Forms\Components\Textarea::make('address')->label('العنوان')->required(),
                                    ]),

                                Forms\Components\Select::make('quotation_type_id')
                                    ->label('نوع العرض')
                                    ->relationship('quotationType', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        self::loadDefaultsForType((int) $state, $set);
                                    }),

                                Forms\Components\DatePicker::make('quotation_date')
                                    ->label('تاريخ العرض')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('Y-m-d')
                                    ->native(false),

                                Forms\Components\TextInput::make('validity_period_days')
                                    ->label('مدة الصلاحية (يوم)')
                                    ->numeric()
                                    ->default(7)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $date = $get('quotation_date');
                                        if ($date && $state) {
                                            $set('valid_until', Carbon::parse($date)->addDays((int) $state)->format('Y-m-d'));
                                        }
                                    }),

                                Forms\Components\DatePicker::make('valid_until')
                                    ->label('صالح حتى')
                                    ->required()
                                    ->displayFormat('Y-m-d')
                                    ->native(false),

                                Forms\Components\TextInput::make('project_name')
                                    ->label('اسم المشروع')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('project_description')
                                    ->label('وصف المشروع')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('installation_location')
                                    ->label('موقع التركيب')
                                    ->placeholder('الإسماعيلية - سرابيوم')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('اللغة والعملة')
                            ->schema([
                                Forms\Components\Select::make('language')
                                    ->label('لغة العرض')
                                    ->options(Quotation::LANGUAGES)
                                    ->default('both')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('currency')
                                    ->label('العملة')
                                    ->options(Quotation::CURRENCIES)
                                    ->default('EGP')
                                    ->required()
                                    ->live()
                                    ->native(false),

                                Forms\Components\TextInput::make('exchange_rate')
                                    ->label('سعر الصرف')
                                    ->helperText('مع العملة الثانوية: الإجمالي الثانوي = الإجمالي × سعر الصرف')
                                    ->numeric()
                                    ->type('text')
                                    ->default(1)
                                    ->step(0.0001)
                                    ->visible(fn (Forms\Get $get) => $get('currency') !== 'EGP'),

                                Forms\Components\Select::make('status')
                                    ->label('الحالة')
                                    ->options(Quotation::STATUSES)
                                    ->default('draft')
                                    ->required()
                                    ->native(false),
                            ])
                            ->columns(2),
                    ]),

                // ====================================
                // Step 2: تفاصيل المشروع
                // ====================================
                Forms\Components\Wizard\Step::make('تفاصيل المشروع')
                    ->icon('heroicon-o-home')
                    ->schema([
                        Forms\Components\Hidden::make('_init_live_calc')
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Forms\Set $set, Forms\Get $get) => static::refreshLivePoultryPricing($set, $get, false)),

                        Forms\Components\Section::make('أبعاد العنبر')
                            ->description('يتم تحديث الحسابات تلقائياً عند تغيير أي حقل')
                            ->schema([
                                Forms\Components\Select::make('hall_type')
                                    ->label('نوع العنبر')
                                    ->options(Quotation::HALL_TYPES)
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\Select::make('pricing_scope')
                                    ->label('نطاق التسعير')
                                    ->options(PoultryPricingScope::options())
                                    ->default(PoultryPricingScope::FullProject->value)
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(true)),

                                Forms\Components\Toggle::make('auto_apply_poultry_pricing')
                                    ->label('تطبيق البنود تلقائياً في خطوة التسعير')
                                    ->default(true)
                                    ->dehydrated(false)
                                    ->live(),

                                Forms\Components\Toggle::make('auto_lines_from_width')
                                    ->label('اقتراح عدد الخطوط من العرض')
                                    ->default(true)
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\TextInput::make('hall_length')
                                    ->label('الطول (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('متر')
                                    ->default(81)
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\TextInput::make('hall_width')
                                    ->label('العرض (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('متر')
                                    ->default(12)
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\TextInput::make('hall_height')
                                    ->label('الارتفاع (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('متر')
                                    ->default(3.5)
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\TextInput::make('service_length')
                                    ->label('منطقة الخدمات (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(10)
                                    ->suffix('م')
                                    ->helperText('يُحفظ أيضاً في dead_zone_meters')
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\TextInput::make('dead_zone_meters')
                                    ->label('المنطقة الميتة (م)')
                                    ->numeric()
                                    ->hidden()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('hall_count')
                                    ->label('عدد العنابر')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),

                                Forms\Components\TextInput::make('tiers')
                                    ->label('عدد الأدوار')
                                    ->numeric()->minValue(1)->default(4)->required()
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\TextInput::make('lines')
                                    ->label('عدد الخطوط')
                                    ->numeric()->minValue(1)->default(4)->required()
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\Select::make('bird_weight_kg')
                                    ->label('وزن الطائر (تسمين)')
                                    ->options(BroilerWeightReference::selectOptions())
                                    ->default('2.100')
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('hall_type'), ['بياض', 'layer'], true))
                                    ->live()
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                ...static::broilerWeightTableSchema(),

                                Forms\Components\Select::make('wall_type')
                                    ->label('نوع الحوائط')
                                    ->options(['sandwich' => 'ساندوتش', 'cement' => 'خرسانة'])
                                    ->default('sandwich')
                                    ->live()
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(true)),

                                Forms\Components\TextInput::make('side_fans_count')
                                    ->label('الشفاطات الجانبية')
                                    ->numeric()
                                    ->integer()
                                    ->helperText('فارغ = تلقائي')
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('hall_type'), ['بياض', 'layer'], true))
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\Select::make('heaters_count')
                                    ->label('الدفايات (اختياري)')
                                    ->options(HeaterOptions::selectOptions())
                                    ->default(0)
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('hall_type'), ['بياض', 'layer'], true))
                                    ->live()
                                    ->afterStateUpdated(static::poultryPricingLiveCallback(false)),

                                Forms\Components\Hidden::make('pricing_preview')
                                    ->dehydrated(false),

                                ...static::livePricingPreviewSchema(),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('السعة والكميات (محسوبة تلقائياً)')
                            ->schema([
                                Forms\Components\TextInput::make('bird_capacity')
                                    ->label('السعة (طائر)')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('back_fans_count')
                                    ->label('المراوح')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('cooling_units')
                                    ->label('التبريد (م)')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('windows_count')
                                    ->label('الشبابيك')
                                    ->numeric()
                                    ->readOnly(),

                                Forms\Components\TextInput::make('cage_count')
                                    ->label('عدد الأقفاص')
                                    ->numeric(),

                                Forms\Components\TextInput::make('average_weight_kg')
                                    ->label('متوسط الوزن (كجم)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->hidden(),
                            ])
                            ->columns(4),

                        Forms\Components\Section::make('ملاحظات')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('ملاحظات للعميل')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ====================================
                // Step 3: الأقسام التقنية
                // ====================================
                Forms\Components\Wizard\Step::make('الأقسام التقنية')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Forms\Components\Repeater::make('sectionAttachments')
                            ->relationship()
                            ->label('الأقسام المختارة')
                            ->schema([
                                Forms\Components\Select::make('quotation_section_id')
                                    ->label('القسم')
                                    ->options(QuotationSection::active()->pluck('title_ar', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Toggle::make('is_visible')
                                    ->label('ظاهر في PDF')
                                    ->default(true),

                                Forms\Components\Textarea::make('content_override_ar')
                                    ->label('تعديل المحتوى (عربي)')
                                    ->helperText('اتركه فارغاً لاستخدام النص الأصلي')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('content_override_en')
                                    ->label('تعديل المحتوى (إنجليزي)')
                                    ->helperText('Leave empty to use original text')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->orderColumn('sort_order')
                            ->addActionLabel('+ إضافة قسم')
                            ->itemLabel(function (array $state) {
                                $sectionId = $state['quotation_section_id'] ?? null;
                                if ($sectionId) {
                                    $section = QuotationSection::find($sectionId);

                                    return $section?->title_ar ?? 'قسم جديد';
                                }

                                return 'قسم جديد';
                            }),
                    ]),

                // ====================================
                // Step 4: التسعير (محرك حسابات مباشر)
                // ====================================
                Forms\Components\Wizard\Step::make('التسعير')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Section::make('التسعير التلقائي')
                            ->description('احسب البنود تلقائياً من أبعاد العنبر والخيارات المختارة')
                            ->icon('heroicon-o-sparkles')
                            ->iconColor('success')
                            ->schema([
                                Forms\Components\Placeholder::make('auto_price_help')
                                    ->label('')
                                    ->content(new HtmlString(
                                        '<div style="background:#f0fdf4;border-right:4px solid #16a34a;padding:12px 16px;border-radius:6px;font-size:11pt;">'
                                        .'<strong>كيف يعمل؟</strong> يقرأ القيم من خطوة «تفاصيل المشروع» ومن إعدادات «تسعير عنابر الدواجن» (لوحة الإعدادات)، ويُولّد بنود الإنشاءات + البطاريات + المشتملات تلقائياً. ستحل البنود محل أي بنود حالية.'
                                        .'</div>'
                                    )),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('autoPrice')
                                        ->label('احسب البنود تلقائياً')
                                        ->icon('heroicon-o-calculator')
                                        ->color('success')
                                        ->size('lg')
                                        ->requiresConfirmation()
                                        ->modalHeading('تأكيد الحساب التلقائي')
                                        ->modalDescription('سيتم استبدال جميع البنود الحالية بالبنود المحسوبة. هل تريد المتابعة؟')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            try {
                                                static::refreshLivePoultryPricing($set, $get, true);
                                                $preview = $get('pricing_preview');
                                                $birds = $preview['computed']['bird_count'] ?? 0;
                                                $itemsCount = count($preview['items'] ?? []);

                                                Notification::make()
                                                    ->title('تم حساب البنود بنجاح')
                                                    ->body("عدد الطيور: {$birds} | البنود: {$itemsCount}")
                                                    ->success()->send();
                                            } catch (\Throwable $e) {
                                                Notification::make()
                                                    ->title('فشل الحساب التلقائي')
                                                    ->body($e->getMessage())
                                                    ->danger()->persistent()->send();
                                            }
                                        }),
                                ])->fullWidth(),

                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\Placeholder::make('birds_preview')
                                        ->label('عدد الطيور المحسوب')
                                        ->content(fn (Forms\Get $get) => number_format((float) ($get('bird_capacity') ?? 0))),
                                    Forms\Components\Placeholder::make('back_fans_preview')
                                        ->label('الشفاطات الخلفية')
                                        ->content(fn (Forms\Get $get) => (int) ($get('back_fans_count') ?? 0)),
                                    Forms\Components\Placeholder::make('cooling_preview')
                                        ->label('وحدات التبريد')
                                        ->content(fn (Forms\Get $get) => (float) ($get('cooling_units') ?? 0)),
                                    Forms\Components\Placeholder::make('windows_preview')
                                        ->label('الشبابيك')
                                        ->content(fn (Forms\Get $get) => (int) ($get('windows_count') ?? 0)),
                                ]),
                            ]),

                        Forms\Components\Section::make('إعدادات التسعير')
                            ->description('العملة تُحدَّد من خطوة «البيانات الأساسية»')
                            ->schema([
                                Forms\Components\Placeholder::make('currency_display')
                                    ->label('العملة')
                                    ->content(fn (Forms\Get $get) => Quotation::CURRENCIES[$get('currency') ?? 'EGP'] ?? ($get('currency') ?? 'EGP')),
                                Forms\Components\TextInput::make('vat_percentage')
                                    ->label('نسبة الضريبة %')
                                    ->numeric()
                                    ->type('text')
                                    ->default(15)
                                    ->suffix('%')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateTotals($set, $get, false)),
                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('خصم على الإجمالي %')
                                    ->numeric()
                                    ->type('text')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateTotals($set, $get, false)),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('بنود العرض')
                            ->description('أضف بنود التسعير — تُحسب الإجماليات تلقائياً عند الخروج من الحقول')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('section_id')
                                            ->label('القسم المرتبط')
                                            ->options(QuotationSection::active()->pluck('title_ar', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),

                                        Forms\Components\TextInput::make('description_ar')
                                            ->label('البند (عربي)')
                                            ->required()
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('description_en')
                                            ->label('البند (إنجليزي)'),

                                        MoneyInput::make('unit_price')
                                            ->label('سعر الوحدة')
                                            ->default(0)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemTotalAndRollup($set, $get)),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('الكمية')
                                            ->numeric()
                                            ->type('text')
                                            ->default(1)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemTotalAndRollup($set, $get)),

                                        Forms\Components\TextInput::make('unit')
                                            ->label('الوحدة')
                                            ->placeholder('قطعة، متر، …')
                                            ->default('piece'),

                                        Forms\Components\TextInput::make('discount_percentage')
                                            ->label('خصم على البند %')
                                            ->numeric()
                                            ->type('text')
                                            ->default(0)
                                            ->suffix('%')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateItemTotalAndRollup($set, $get)),

                                        MoneyInput::make('total_price')
                                            ->label('إجمالي البند')
                                            ->default(0)
                                            ->asReadOnly(),

                                        Forms\Components\Toggle::make('is_taxable')
                                            ->label('خاضع للضريبة')
                                            ->default(true),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('الترتيب')
                                            ->numeric()
                                            ->default(0),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('ملاحظات')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->orderColumn('sort_order')
                                    ->addActionLabel('+ إضافة بند')
                                    ->itemLabel(function (array $state): string {
                                        $label = $state['description_ar'] ?? 'بند جديد';
                                        $line = QuotationCalculator::calculateItemTotal([
                                            'unit_price' => $state['unit_price'] ?? 0,
                                            'quantity' => $state['quantity'] ?? 0,
                                            'discount_percentage' => $state['discount_percentage'] ?? 0,
                                        ]);

                                        return $label.' — '.QuotationCalculator::format($line);
                                    })
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateTotals($set, $get, false)),
                            ]),

                        Forms\Components\Section::make('الحسابات المباشرة')
                            ->description('تُحدَّث مع كل تعديل على البنود أو النسب')
                            ->icon('heroicon-o-calculator')
                            ->iconColor('warning')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    MoneyInput::make('subtotal')
                                        ->label('المجموع قبل الخصم والضريبة')
                                        ->default(0)
                                        ->asReadOnly(),

                                    MoneyInput::make('discount_amount')
                                        ->label('قيمة الخصم على الإجمالي')
                                        ->default(0)
                                        ->asReadOnly(),

                                    MoneyInput::make('vat_amount')
                                        ->label('قيمة الضريبة')
                                        ->default(0)
                                        ->asReadOnly(),

                                    Forms\Components\Placeholder::make('after_discount_display')
                                        ->label('المجموع بعد الخصم (قبل الضريبة)')
                                        ->content(function (Forms\Get $get) {
                                            $subtotal = QuotationCalculator::toFloat($get('subtotal'));
                                            $discount = QuotationCalculator::toFloat($get('discount_amount'));
                                            $after = max(0, $subtotal - $discount);
                                            $sym = QuotationCalculator::currencySymbol($get('currency') ?? 'EGP');

                                            return new HtmlString(
                                                '<div style="font-size:14pt;font-weight:bold;direction:ltr;text-align:left;">'
                                                .QuotationCalculator::format($after).' '.$sym
                                                .'</div>'
                                            );
                                        }),
                                ]),

                                Forms\Components\Placeholder::make('final_total_display')
                                    ->label('')
                                    ->content(function (Forms\Get $get) {
                                        $total = QuotationCalculator::toFloat($get('total_amount'));
                                        $sym = QuotationCalculator::currencySymbol($get('currency') ?? 'EGP');
                                        $words = $total > 0 ? QuotationCalculator::toArabicWords($total) : 'صفر';

                                        return new HtmlString(
                                            '<div style="background:linear-gradient(135deg,#b91c1c 0%,#7f1d1d 100%);padding:20px;border-radius:10px;text-align:center;">'
                                            .'<div style="color:#fff;font-size:11pt;margin-bottom:6px;opacity:.95">الإجمالي النهائي</div>'
                                            .'<div style="color:#fff;font-size:28pt;font-weight:bold;direction:ltr;font-family:monospace;">'
                                            .QuotationCalculator::format($total).' '.$sym
                                            .'</div>'
                                            .'<div style="color:rgba(255,255,255,.92);font-size:11pt;margin-top:10px;border-top:1px solid rgba(255,255,255,.25);padding-top:8px">'
                                            .'بالحروف: <strong>'.e($words).'</strong>'
                                            .'</div></div>'
                                        );
                                    }),

                                MoneyInput::make('total_amount')
                                    ->label('الإجمالي النهائي (يُحفظ)')
                                    ->default(0)
                                    ->asReadOnly(),

                                Forms\Components\Placeholder::make('validation_warning')
                                    ->label('')
                                    ->content(function (Forms\Get $get) {
                                        $items = $get('items') ?? [];
                                        $total = QuotationCalculator::toFloat($get('total_amount'));
                                        $warnings = [];

                                        if (! is_array($items) || count($items) === 0) {
                                            $warnings[] = 'لا توجد بنود في العرض';
                                        }

                                        if ($total === 0.0 && is_array($items) && count($items) > 0) {
                                            $warnings[] = 'الإجمالي صفر — تأكد من الأسعار والكميات';
                                        }

                                        if ($total > 50_000_000) {
                                            $warnings[] = 'الإجمالي أكبر من 50 مليون — تأكد من الصحة';
                                        }

                                        foreach ($items as $idx => $item) {
                                            if (! is_array($item)) {
                                                continue;
                                            }
                                            $price = QuotationCalculator::toFloat($item['unit_price'] ?? 0);
                                            if ($price > 10_000_000) {
                                                $warnings[] = 'البند #'.($idx + 1).' سعر الوحدة مرتفع جداً';
                                            }
                                        }

                                        if ($warnings === []) {
                                            return new HtmlString(
                                                '<div style="background:#d1fae5;border-right:4px solid #10b981;padding:12px;border-radius:6px;color:#065f46">'
                                                .'الحسابات متسقة'
                                                .'</div>'
                                            );
                                        }

                                        return new HtmlString(
                                            '<div style="background:#fef3c7;border-right:4px solid #f59e0b;padding:12px;border-radius:6px;color:#78350f">'
                                            .e(implode(' — ', $warnings))
                                            .'</div>'
                                        );
                                    }),
                            ])
                            ->columns(1),
                    ]),

                // ====================================
                // Step 5: البنود والشروط
                // ====================================
                Forms\Components\Wizard\Step::make('البنود والشروط')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Repeater::make('termAttachments')
                            ->relationship()
                            ->label('البنود المختارة')
                            ->schema([
                                Forms\Components\Select::make('quotation_term_id')
                                    ->label('البند')
                                    ->options(QuotationTerm::active()->pluck('title_ar', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Toggle::make('is_visible')
                                    ->label('ظاهر في PDF')
                                    ->default(true),

                                Forms\Components\KeyValue::make('variables_values')
                                    ->label('قيم المتغيرات')
                                    ->keyLabel('المتغير')
                                    ->valueLabel('القيمة')
                                    ->columnSpanFull()
                                    ->helperText('مثال: warranty_months = 12'),

                                Forms\Components\Textarea::make('content_override')
                                    ->label('تعديل النص (اختياري)')
                                    ->helperText('اتركه فارغاً لاستخدام النص الأصلي')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->orderColumn('sort_order')
                            ->addActionLabel('+ إضافة بند')
                            ->itemLabel(function (array $state) {
                                $termId = $state['quotation_term_id'] ?? null;
                                if ($termId) {
                                    $term = QuotationTerm::find($termId);

                                    return $term?->title_ar ?? 'بند جديد';
                                }

                                return 'بند جديد';
                            }),
                    ]),

                // ====================================
                // Step 6: المراجعة والملاحظات
                // ====================================
                Forms\Components\Wizard\Step::make('المراجعة والملاحظات')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Section::make('ملاحظات داخلية')
                            ->schema([
                                Forms\Components\Textarea::make('internal_notes')
                                    ->label('ملاحظات داخلية (لا تظهر في PDF)')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('المرفقات')
                            ->schema([
                                Forms\Components\FileUpload::make('attachments')
                                    ->label('ملفات مرفقة')
                                    ->multiple()
                                    ->directory('quotations/attachments')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('رقم العرض')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->wrap()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quotationType.name')
                    ->label('النوع')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('تاريخ العرض')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('صالح حتى')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn (Quotation $r) => $r->is_expired ? 'danger' : null),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('قبل الضريبة')
                    ->money(fn (Quotation $r) => $r->currency ?? 'EGP')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vat_amount')
                    ->label('الضريبة')
                    ->money(fn (Quotation $r) => $r->currency ?? 'EGP')
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money(fn (Quotation $r) => $r->currency ?? 'EGP')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => (float) $state > 1_000_000 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Quotation::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'converted' => 'primary',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        'sent' => 'info',
                        'draft' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('revision_number')
                    ->label('الإصدار')
                    ->badge()
                    ->color('secondary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Quotation::STATUSES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('quotation_type_id')
                    ->label('نوع العرض')
                    ->relationship('quotationType', 'name')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('تنتهي قريباً (3 أيام)')
                    ->query(fn ($query) => $query
                        ->where('valid_until', '<=', now()->addDays(3))
                        ->where('valid_until', '>=', now())
                        ->whereNotIn('status', ['converted', 'rejected'])),

                Tables\Filters\TrashedFilter::make()->label('المحذوفة'),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadPdf')
                    ->label('تحميل PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Quotation $record) {
                        return app(QuotationGenerator::class)->downloadPdf($record);
                    }),

                Tables\Actions\Action::make('preview')
                    ->label('معاينة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->openUrlInNewTab()
                    ->url(fn (Quotation $record) => route('quotations.preview', $record)),

                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),

                Tables\Actions\Action::make('convert')
                    ->label('تحويل لعقد')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'approved'
                        && ! $record->contract_id
                        && auth()->user()?->can('quotations.convert')
                    )
                    ->form([
                        Forms\Components\Section::make('بيانات إضافية للعقد')
                            ->schema([
                                Forms\Components\Select::make('contract_type_id')
                                    ->label('نوع العقد')
                                    ->relationship('contractType', 'name')
                                    ->options(ContractType::pluck('name', 'id'))
                                    ->required(),

                                Forms\Components\DatePicker::make('contract_date')
                                    ->label('تاريخ العقد')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TextInput::make('manufacturing_days')
                                    ->label('مدة التصنيع (يوم)')
                                    ->numeric()
                                    ->default(105),

                                Forms\Components\DatePicker::make('expected_delivery_date')
                                    ->label('تاريخ التسليم المتوقع')
                                    ->default(now()->addDays(105)),
                            ])->columns(2),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('تحويل العرض إلى عقد')
                    ->modalDescription('سيتم إنشاء عقد جديد بنفس بيانات العرض وتوليد جدول دفعات تلقائياً')
                    ->modalSubmitActionLabel('تحويل الآن')
                    ->action(function ($record, array $data) {
                        try {
                            $converter = app(QuotationToContractConverter::class);
                            $contract = $converter->convert($record, $data);

                            Notification::make()
                                ->title('✅ تم التحويل بنجاح')
                                ->body("تم إنشاء العقد رقم {$contract->contract_number}")
                                ->success()
                                ->actions([
                                    Action::make('view')
                                        ->label('فتح العقد')
                                        ->url(route('filament.admin.resources.contracts.edit', $contract))
                                        ->button(),
                                ])
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ فشل التحويل')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('send_to_customer')
                    ->label('إرسال للعميل')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => $record->customer->email
                        && auth()->user()?->can('quotations.send')
                        && in_array($record->status, ['draft', 'sent'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('إرسال عرض السعر للعميل')
                    ->modalDescription(fn ($record) => "سيتم الإرسال إلى: {$record->customer->email}")
                    ->action(function ($record) {
                        try {
                            $mail = new QuotationMail($record, '');

                            Mail::to($record->customer->email)
                                ->cc(auth()->user()->email)
                                ->send($mail);

                            $record->update([
                                'status' => 'sent',
                                'sent_at' => now(),
                            ]);

                            ChangeLog::create([
                                'subject_type' => Quotation::class,
                                'subject_id' => $record->id,
                                'event' => 'sent',
                                'reason' => "تم إرسال العرض عبر البريد إلى {$record->customer->email}",
                                'user_id' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('✅ تم الإرسال للعميل بنجاح')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ فشل الإرسال')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('send_email')
                    ->label('إرسال لبريد آخر')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->visible(fn ($record) => auth()->user()?->can('quotations.send')
                    )
                    ->form([
                        Forms\Components\TextInput::make('to_email')
                            ->label('إلى')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('subject')
                            ->label('الموضوع')
                            ->default(fn ($record) => "عرض سعر #{$record->quotation_number}")
                            ->required(),
                        Forms\Components\Textarea::make('custom_message')
                            ->label('رسالة إضافية (اختياري)')
                            ->rows(4),
                        Forms\Components\Toggle::make('cc_self')
                            ->label('إرسال نسخة لي')
                            ->default(true),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $mail = new QuotationMail(
                                $record,
                                $data['custom_message'] ?? ''
                            );

                            Mail::to($data['to_email'])
                                ->when($data['cc_self'] ?? false, fn ($m) => $m->cc(auth()->user()->email))
                                ->send($mail);

                            ChangeLog::create([
                                'subject_type' => Quotation::class,
                                'subject_id' => $record->id,
                                'event' => 'sent',
                                'reason' => "تم إرسال العرض عبر البريد إلى {$data['to_email']}",
                                'user_id' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('✅ تم الإرسال بنجاح')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ فشل الإرسال')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('send_whatsapp')
                    ->label('إرسال واتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn ($record) => $record->customer->phone
                        && auth()->user()?->can('quotations.send')
                    )
                    ->form([
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الواتساب')
                            ->default(fn ($record) => $record->customer->phone)
                            ->required()
                            ->helperText('بدون 00 أو + (مثلاً: 201026253004)'),
                    ])
                    ->action(function ($record, array $data) {
                        $sharing = app(QuotationSharingService::class);
                        $url = $sharing->getWhatsAppLink($record, $data['phone']);

                        ChangeLog::create([
                            'subject_type' => Quotation::class,
                            'subject_id' => $record->id,
                            'event' => 'shared',
                            'reason' => "تم مشاركة العرض عبر واتساب إلى {$data['phone']}",
                            'user_id' => auth()->id(),
                        ]);

                        return redirect()->away($url);
                    }),

                Tables\Actions\Action::make('copy_link')
                    ->label('نسخ رابط العرض')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->action(function ($record) {
                        $url = app(QuotationSharingService::class)
                            ->getPublicPreviewUrl($record);

                        Notification::make()
                            ->title('الرابط جاهز')
                            ->body($url)
                            ->success()
                            ->duration(20000)
                            ->send();
                    }),

                Tables\Actions\Action::make('duplicate')
                    ->label('نسخة جديدة')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $new = $record->replicate(['quotation_number', 'status', 'sent_at', 'approved_at', 'rejected_at', 'converted_at', 'contract_id']);
                        $new->status = 'draft';
                        $new->quotation_date = now();
                        $new->valid_until = now()->addDays($record->validity_period_days ?? 7);
                        $new->parent_quotation_id = $record->id;
                        $new->revision_number = $record->revisions()->max('revision_number') + 1;
                        $new->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('quotation_date', 'desc');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->can('quotations.update')) {
            return true;
        }

        if ($user->can('quotations.update_own')) {
            return $record->created_by === $user->id
                && in_array($record->status, ['draft', 'sent']);
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('quotations.delete') ?? false;
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            SectionAttachmentsRelationManager::class,
            TermAttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotations::route('/'),
            'create' => CreateQuotation::route('/create'),
            'edit' => EditQuotation::route('/{record}/edit'),
        ];
    }

    // =================== Helper Methods ===================

    protected static function loadDefaultsForType(int $typeId, Forms\Set $set): void
    {
        $type = QuotationType::find($typeId);
        if (! $type) {
            return;
        }

        $set('validity_period_days', $type->default_validity_days);

        // تحديث valid_until
        $date = now()->format('Y-m-d');
        $set('valid_until', now()->addDays((int) $type->default_validity_days)->format('Y-m-d'));

        // تحميل الأقسام الافتراضية
        $sectionIds = $type->default_sections ?? [];
        if (! empty($sectionIds)) {
            $sections = QuotationSection::whereIn('id', $sectionIds)->orderBy('sort_order')->get();
            $set('sectionAttachments', $sections->map(fn ($s, $idx) => [
                'quotation_section_id' => $s->id,
                'is_visible' => true,
                'sort_order' => $idx,
            ])->values()->toArray());
        }

        // تحميل البنود الافتراضية
        $termIds = $type->default_terms ?? [];
        if (! empty($termIds)) {
            $terms = QuotationTerm::whereIn('id', $termIds)->orderBy('sort_order')->get();
            $set('termAttachments', $terms->map(fn ($t, $idx) => [
                'quotation_term_id' => $t->id,
                'is_visible' => true,
                'sort_order' => $idx,
            ])->values()->toArray());
        }
    }

    protected static function updateItemTotalAndRollup(Forms\Set $set, Forms\Get $get): void
    {
        $line = QuotationCalculator::calculateItemTotal([
            'unit_price' => $get('unit_price'),
            'quantity' => $get('quantity'),
            'discount_percentage' => $get('discount_percentage'),
        ]);
        $set('total_price', QuotationCalculator::formatDecimalString($line));
        // داخل صف الـ Repeater: Get لا يرى بنود الـ root — نصعد مستويين
        self::recalculateTotals($set, $get, true);
    }

    /**
     * @param  bool  $fromRepeaterItem  true عند استدعاء الحساب من داخل صف بند (مسار ../../ حتى حقول الـ Wizard)
     */
    protected static function recalculateTotals(Forms\Set $set, Forms\Get $get, bool $fromRepeaterItem): void
    {
        if ($fromRepeaterItem) {
            $p = '../../';
            $items = $get($p.'items');
            if (! is_array($items)) {
                $items = [];
            }
            $data = [
                'items' => $items,
                'discount_percentage' => $get($p.'discount_percentage'),
                'discount_amount' => $get($p.'discount_amount'),
                'vat_percentage' => $get($p.'vat_percentage'),
                'secondary_currency' => $get($p.'secondary_currency'),
                'exchange_rate' => $get($p.'exchange_rate'),
            ];
        } else {
            $p = '';
            $items = $get('items');
            if (! is_array($items)) {
                $items = $get() ?? [];
            }
            if (! is_array($items)) {
                $items = $get('../items') ?? [];
            }
            if (! is_array($items)) {
                $items = [];
            }
            $data = [
                'items' => $items,
                'discount_percentage' => $get('discount_percentage') ?? $get('../discount_percentage'),
                'discount_amount' => $get('discount_amount') ?? $get('../discount_amount'),
                'vat_percentage' => $get('vat_percentage') ?? $get('../vat_percentage'),
                'secondary_currency' => $get('secondary_currency') ?? $get('../secondary_currency'),
                'exchange_rate' => $get('exchange_rate') ?? $get('../exchange_rate'),
            ];
        }

        $result = QuotationCalculator::calculateQuotation($data);

        $set($p.'subtotal', QuotationCalculator::formatDecimalString($result['subtotal']));
        $set($p.'discount_amount', QuotationCalculator::formatDecimalString($result['discount_amount']));
        $set($p.'vat_amount', QuotationCalculator::formatDecimalString($result['vat_amount']));
        $set($p.'total_amount', QuotationCalculator::formatDecimalString($result['total_amount']));

        if ($result['total_amount_secondary'] !== null) {
            $set($p.'total_amount_secondary', QuotationCalculator::formatDecimalString($result['total_amount_secondary']));
        }
    }
}
