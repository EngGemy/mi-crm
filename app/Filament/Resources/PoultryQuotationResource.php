<?php

namespace App\Filament\Resources;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Filament\Concerns\HasLivePoultryPricing;
use App\Filament\Resources\PoultryQuotationResource\Pages;
use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Services\Fx\DailyUsdEgpRate;
use App\Services\Poultry\SavedClientCatalog;
use App\Services\Pricing\PricingCardImageGenerator;
use App\Services\Poultry\ConstructionSummary;
use App\Services\Poultry\PoultryConfigLoader;
use App\Services\Poultry\VentilationOffer;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Services\Poultry\PoultryQuoteAccess;
use Illuminate\Database\Eloquent\Builder;
use App\Support\BroilerWeightReference;
use App\Support\HeaterOptions;
use App\Support\LayerBaseLookups;
use App\Support\SiloCapacity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class PoultryQuotationResource extends Resource
{
    use HasLivePoultryPricing;

    protected static ?string $model = PoultryQuotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'حاسبة الأسعار';

    protected static ?string $modelLabel = 'حساب سعر';

    protected static ?string $pluralModelLabel = 'حاسبة الأسعار';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\PermissionGate::allows(
            'quotations.view_any',
            'quotations.view_own',
            'quotations.create',
        );
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && PoultryQuoteAccess::seesOwnQuotesOnly($user)) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $live = static::poultryPricingLiveCallback(false);

        return $form
            ->schema([
                Forms\Components\Hidden::make('_init_live_calc')
                    ->dehydrated(false)
                    ->afterStateHydrated(fn (Set $set, Get $get) => static::refreshLivePoultryPricing($set, $get, false)),
                Forms\Components\Hidden::make('pricing_preview')->dehydrated(false),
                Forms\Components\Hidden::make('vat_percentage')->default(0),

                Forms\Components\Group::make([
                    Forms\Components\Group::make([
                        ...static::customerStepSchema($live),
                        ...static::quoteSetupStepSchema($live),
                        ...static::barnStepSchema($live),
                        ...static::equipmentStepSchema($live),
                        ...static::extrasStepSchema(),
                    ])
                        ->columnSpan(['default' => 1, 'lg' => 7])
                        ->extraAttributes(['class' => 'pq-form-main']),

                    Forms\Components\Group::make(static::summaryStepSchema())
                        ->columnSpan(['default' => 1, 'lg' => 5])
                        ->extraAttributes(['class' => 'pq-form-summary']),
                ])
                    ->columns(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'pq-exhibition-layout']),
            ]);
    }

    /** @param  \Closure  $live */
    protected static function customerStepSchema(\Closure $live): array
    {
        return [
            Forms\Components\Section::make('① العميل')
                ->description('الاسم والموبايل كافيان للمعرض — باقي البيانات اختيارية')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('client_name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('اسم العميل'),

                    Forms\Components\TextInput::make('client_phone')
                        ->label('الموبايل')
                        ->required()
                        ->tel()
                        ->placeholder('+2010xxxxxxx'),

                    Forms\Components\Hidden::make('customer_id'),

                    Forms\Components\Select::make('saved_client')
                        ->label('عميل محفوظ (اختياري)')
                        ->options(fn () => SavedClientCatalog::options())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->dehydrated(false)
                        ->placeholder('من العملاء أو العملاء المحتملين')
                        ->helperText('العميل المتسجل من بره يظهر لكل السيلز، ومعاه اسم المندوب اللي أضافه.')
                        ->live()
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?PoultryQuotation $record): void {
                            if ($record?->customer_id) {
                                $component->state('customer:'.$record->customer_id);
                            }
                        })
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === null || $state === '') {
                                $set('customer_id', null);

                                return;
                            }

                            $fields = SavedClientCatalog::fieldsFor($state);
                            if ($fields === null) {
                                return;
                            }

                            foreach ($fields as $field => $value) {
                                $set($field, $value);
                            }
                        })
                        ->columnSpanFull(),

                    Forms\Components\Section::make('بيانات إضافية (اختياري)')
                        ->schema([
                            Forms\Components\TextInput::make('client_company')
                                ->label('الشركة / المزرعة')
                                ->maxLength(255),
                            Forms\Components\Select::make('client_country')
                                ->label('الدولة')
                                ->options(fn () => Lookup::ofType(Lookup::TYPE_COUNTRY)->pluck('label_ar', 'label_ar')->all())
                                ->searchable()
                                ->native(false),
                            Forms\Components\Select::make('client_location')
                                ->label('المحافظة')
                                ->options(fn () => Lookup::ofType(Lookup::TYPE_LOCATION)->pluck('label_ar', 'label_ar')->all())
                                ->searchable()
                                ->native(false),
                            Forms\Components\TextInput::make('client_email')
                                ->label('البريد')
                                ->email(),
                            Forms\Components\TextInput::make('client_address')
                                ->label('العنوان')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('client_notes')
                                ->label('ملاحظات')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->collapsed()
                        ->compact(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ];
    }

    /** @param  \Closure  $live */
    protected static function quoteSetupStepSchema(\Closure $live): array
    {
        return [
            Forms\Components\Section::make('② نوع العرض')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Select::make('project_type')
                        ->label('النوع')
                        ->options(PoultryProjectType::options())
                        ->default(PoultryProjectType::Broiler->value)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get) use ($live) {
                            $set('height', '4');
                            if (PoultryProjectType::tryFrom((string) $get('project_type'))?->isLayer()) {
                                static::applyLayerSpecDefaults($set, $get);
                            } else {
                                $set('birds_per_nest', null);
                            }
                            $live($set, $get);
                        }),

                    Forms\Components\Select::make('pricing_scope')
                        ->label('نطاق التسعير')
                        ->options(PoultryPricingScope::options())
                        ->default(PoultryPricingScope::BatteriesOnly->value)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated($live),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ];
    }

    /** @param  \Closure  $live */
    protected static function barnStepSchema(\Closure $live): array
    {
        return [
            Forms\Components\Section::make('③ أبعاد العنبر')
                ->description('أدخل الأبعاد → الحساب يتحدث فوراً على اليمين')
                ->icon('heroicon-o-home-modern')
                ->schema([
                    Forms\Components\Select::make('internal_columns')
                        ->label('الأعمدة الداخلية')
                        ->options([
                            0 => '0',
                            1 => '1',
                            2 => '2',
                            3 => '3',
                            4 => '4',
                        ])
                        ->default(0)
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated($live),

                    Forms\Components\Select::make('roof_type')
                        ->label('نوع السقف')
                        ->options([
                            'flat' => 'مستوي',
                            'gable' => 'مائل',
                        ])
                        ->default('flat')
                        ->required()
                        ->native(false)
                        ->visible(fn (Get $get) => static::isBroilerProject($get))
                        ->dehydrated(fn (Get $get) => static::isBroilerProject($get))
                        ->live()
                        ->afterStateUpdated($live),

                    Forms\Components\Select::make('silo_capacity_id')
                        ->label('سعة السايلو')
                        ->options(fn () => Lookup::options(Lookup::TYPE_SILO_CAPACITY))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_SILO_CAPACITY))
                        ->helperText('تتحدد من عدد الطيور: حتى 42,000 = 11 طن، فوقها حتى 52,000 = 14 طن، من 52,000 حتى 62,000 = 17 طن، وفوق 62,000 = 25 طن.')
                        ->disabled()
                        ->dehydrated()
                        ->native(false)
                        ->searchable()
                        ->live(),

                    Forms\Components\Select::make('silos_count')
                        ->label('عدد السيلوهات')
                        ->options([
                            1 => '1',
                            2 => '2',
                            3 => '3',
                        ])
                        ->default(1)
                        ->native(false)
                        ->required(fn (Get $get): bool => SiloCapacity::allowsSiloCountChoice((int) $get('bird_count')))
                        ->hidden(fn (Get $get): bool => ! SiloCapacity::allowsSiloCountChoice((int) $get('bird_count')))
                        ->dehydrated()
                        ->live(),

                    Forms\Components\TextInput::make('length')
                        ->label('الطول')
                        ->required()->numeric()->step(0.01)->default(81)->minValue(1)->suffix('م')
                        ->live(onBlur: true)->afterStateUpdated($live),

                    Forms\Components\TextInput::make('width')
                        ->label('العرض')
                        ->required()->numeric()->step(0.01)->default(12)->minValue(1)->suffix('م')
                        ->live(onBlur: true)->afterStateUpdated($live),

                    Forms\Components\TextInput::make('height')
                        ->label('الارتفاع')
                        ->required()->numeric()->step(0.01)
                        ->default('4')
                        ->minValue(0.1)->suffix('م')
                        ->live(onBlur: true)
                        ->afterStateUpdated($live)
                        ->helperText(function (Get $get): ?string {
                            if (static::isAutoExitProject($get)) {
                                return '3 أدوار من 3.60 م، و4 أدوار من 4.40 م. يمكن كتابة الارتفاع بكسر مثل 3.65.';
                            }

                            return static::isBroilerProject($get)
                                ? 'سقف مستوي: أقل من 2.9 م مرفوض، ومن 3 إلى 3.5 م يختار 3 أدوار، ومن 3.7 إلى 4.5 م يختار 4 أدوار. السقف المائل أقل بـ 30 سم.'
                                : null;
                        })
                        ->hint(fn (Get $get): ?string => static::barnTierRule($get, (float) ($get('height') ?: 0))['message'])
                        ->hintColor('danger')
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $message = static::barnTierRule($get, (float) $value)['message'];
                                if ($message !== null) {
                                    $fail($message);
                                }
                            },
                        ]),

                    Forms\Components\TextInput::make('battery_height_m')
                        ->label('ارتفاع التخريج الآلي')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('م')
                        ->visible(fn (Get $get): bool => static::isAutoExitProject($get))
                        ->dehydrated(fn (Get $get): bool => static::isAutoExitProject($get))
                        ->helperText('يبدأ من 3.60 م لثلاث أدوار، ومن 4.40 م لأربع أدوار. يمكن رفع الرقم بكسر.')
                        ->live(onBlur: true)
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                if (! static::isAutoExitProject($get) || $value === null || $value === '') {
                                    return;
                                }

                                $start = (new PoultryTechnicalCalculator)->autoExitHeightStart((int) ($get('tiers') ?: 3));
                                if ((float) $value + 0.0001 < $start) {
                                    $fail('ارتفاع التخريج الآلي يبدأ من '.number_format($start, 2, '.', '').' م');
                                }
                            },
                        ]),

                    Forms\Components\TextInput::make('lines')
                        ->label('الخطوط')
                        ->required()->numeric()->integer()->default(4)->minValue(1)->maxValue(12)
                        ->helperText(fn (Get $get): ?string => static::isBroilerProject($get)
                            ? 'عرض 11–13.5 م = 4 خطوط. ومن 13.6–16 م = 5، ومن 16.10–18 م = 6، بشرط عدم وجود أعمدة.'
                            : null)
                        ->live(onBlur: true)->afterStateUpdated($live),

                    Forms\Components\Select::make('tiers')
                        ->label('الأدوار')
                        ->options([
                            3 => '3 أدوار',
                            4 => '4 أدوار',
                            5 => '5 أدوار',
                        ])
                        ->default(4)
                        ->required()
                        ->native(false)
                        ->live()
                        ->disabled(function (Get $get): bool {
                            if (! static::isBroilerProject($get)) {
                                return false;
                            }

                            $height = (float) ($get('height') ?: 0);

                            return $height > 0 && static::barnTierRule($get, $height)['tiers'] !== null;
                        })
                        ->dehydrated()
                        ->helperText(fn (Get $get): ?string => static::isBroilerProject($get)
                            ? 'يتحدد من الارتفاع ونوع السقف داخل النطاقات المعتمدة.'
                            : null)
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $set('tiers', (int) $state);
                            static::refreshLivePoultryPricing($set, $get, false, (int) $state);
                        }),

                    Forms\Components\TextInput::make('barns_count')
                        ->label('عدد العنابر')
                        ->required()->numeric()->integer()->default(1)->minValue(1)
                        ->live(onBlur: true)->afterStateUpdated($live),

                    Forms\Components\Select::make('bird_weight_kg')
                        ->label('وزن الطائر')
                        ->options(BroilerWeightReference::selectOptions())
                        ->default('2.100')
                        ->visible(fn (Get $get) => PoultryProjectType::tryFrom((string) ($get('project_type') ?: 'broiler'))?->isBroiler() ?? false)
                        ->native(false)
                        ->live()
                        ->afterStateUpdated($live),

                    Forms\Components\TextInput::make('bird_price_usd')
                        ->label('سعر الطائر')
                        ->numeric()
                        ->suffix('$')
                        ->readOnly(fn (Get $get): bool => PoultryProjectType::tryFrom((string) $get('project_type'))?->birdPriceUsd((int) ($get('tiers') ?: 4)) !== null)
                        ->default(2.8)
                        ->helperText(fn (Get $get): string => PoultryProjectType::tryFrom((string) $get('project_type')) === PoultryProjectType::LayerRearing
                            ? 'سعر طائر تربية البياض لم يُحدد. اكتبه بالدولار ويتحول للجنيه بسعر الصرف.'
                            : 'يتحدد من نوع العرض وعدد الأدوار، ويتحول للجنيه بسعر الصرف.')
                        ->live(onBlur: true)
                        ->afterStateUpdated($live),

                    Forms\Components\TextInput::make('bird_price')
                        ->label('سعر الطائر بالجنيه')
                        ->numeric()
                        ->suffix('ج.م')
                        ->readOnly()
                        ->default(fn () => round(2.8 * app(DailyUsdEgpRate::class)->rate(), 2))
                        ->live(),

                    Forms\Components\TextInput::make('exchange_rate')
                        ->label('سعر الصرف')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('ج/$')
                        ->default(fn () => app(DailyUsdEgpRate::class)->rate())
                        ->helperText(function (): string {
                            $quote = app(DailyUsdEgpRate::class)->current();
                            $when = filled($quote['as_of'] ?? null) ? ' بتاريخ '.$quote['as_of'] : '';

                            return 'سعر الدولار العالمي اليومي من '.$quote['source'].$when.'. يمكن تعديله لهذا العرض فقط.';
                        })
                        ->live(onBlur: true)
                        ->afterStateUpdated($live),

                    Forms\Components\Section::make('خيارات متقدمة')
                        ->schema([
                            Forms\Components\Toggle::make('auto_service_deduction')
                                ->label('خصم الخدمات تلقائياً')
                                ->default(true)
                                ->dehydrated(false)
                                ->live()
                                ->afterStateUpdated($live),

                            Forms\Components\Toggle::make('auto_lines_from_width')
                                ->label('اقتراح الخطوط من العرض')
                                ->default(true)
                                ->dehydrated(false)
                                ->live()
                                ->afterStateUpdated($live),

                            Forms\Components\TextInput::make('service_length')
                                ->label('منطقة الخدمات')
                                ->numeric()->step(0.01)->suffix('م')
                                ->disabled(fn (Get $get) => $get('auto_service_deduction') !== false)
                                ->dehydrated()
                                ->live(onBlur: true)
                                ->afterStateUpdated($live),

                            Forms\Components\Select::make('wall_type')
                                ->label('نوع الحوائط')
                                ->options(['sandwich' => 'ساندوتش بانل', 'cement' => 'خرسانة'])
                                ->default('sandwich')
                                ->visible(fn (Get $get) => ! static::isBroilerProject($get) && (static::isLayerProject($get) || static::showsWallTypeField($get)))
                                ->dehydrated(fn (Get $get) => ! static::isBroilerProject($get))
                                ->native(false)
                                ->live()
                                ->afterStateUpdated($live),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->collapsed()
                        ->compact(),
                ])
                ->columns(['default' => 2, 'sm' => 3]),
        ];
    }

    protected static function applyLayerSpecDefaults(Set $set, Get $get): void
    {
        $specs = $get('layer_specs');
        if (! is_array($specs)) {
            $specs = [];
        }

        foreach (LayerBaseLookups::defaultState() as $key => $id) {
            if (blank($specs[$key] ?? null) && $id !== null) {
                $specs[$key] = $id;
            }
        }

        $set('layer_specs', $specs);
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function extrasStepSchema(): array
    {
        return [
            Forms\Components\Section::make('⑤ المشتملات والإنشاءات')
                ->description('المشتملات عرض واحد يجمع التهوية والتبريد. الإنشاءات عرض واحد. الرقم الأساسي من الإعدادات، والمندوب يقدر يغيّره داخل العرض.')
                ->icon('heroicon-o-sparkles')
                ->schema([
                    Forms\Components\Toggle::make('include_ventilation')
                        ->label('العميل عاوز المشتملات')
                        ->helperText('عرض واحد للتهوية والتبريد. بعد الحفظ يظهر زر تحميل عرض سعر المشتملات.')
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('ventilation_side_fans')
                        ->label('الشفاطات الجانبية')
                        ->options([
                            4 => '4',
                            6 => '6',
                            8 => '8',
                            10 => '10',
                        ])
                        ->placeholder('اختار 4 أو 6 أو 8 أو 10')
                        ->helperText('للتسمين فقط. العدد اختيار من الأربعة دول.')
                        ->native(false)
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('include_ventilation') && static::isBroilerProject($get)),

                    Forms\Components\TextInput::make('ventilation_heating_price')
                        ->label('سعر منظومة التدفئة')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ج.م')
                        ->helperText('الرقم الأساسي من الإعدادات، وتقدر تغيّره لهذا العرض.')
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('include_ventilation') && static::isBroilerProject($get))
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, mixed $state): void {
                            static::fillOfferBase($component, $state, VentilationOffer::baseHeating());
                        }),

                    Forms\Components\TextInput::make('ventilation_electricity_price')
                        ->label('سعر الكهرباء والكنترول والإنارة')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ج.م')
                        ->helperText('الرقم الأساسي من الإعدادات، وتقدر تغيّره لهذا العرض.')
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('include_ventilation'))
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, mixed $state): void {
                            static::fillOfferBase($component, $state, VentilationOffer::baseElectricity());
                        }),

                    Forms\Components\TextInput::make('ventilation_monitor_price')
                        ->label('سعر جهاز المونيتور')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ج.م')
                        ->helperText('الرقم الأساسي من الإعدادات، وتقدر تغيّره لهذا العرض.')
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('include_ventilation'))
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, mixed $state): void {
                            static::fillOfferBase($component, $state, VentilationOffer::baseMonitor());
                        }),

                    Forms\Components\Placeholder::make('ventilation_preview')
                        ->label('ملخص المشتملات')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => (bool) $get('include_ventilation'))
                        ->content(function (Get $get) {
                            $preview = $get('pricing_preview');
                            $birds = (int) (is_array($preview) ? ($preview['computed']['bird_count'] ?? 0) : 0);
                            if ($birds <= 0) {
                                $birds = (int) ($get('bird_count') ?? 0);
                            }
                            $length = (int) round((float) ($get('length') ?? 0));
                            if ($birds <= 0 || $length <= 0) {
                                return 'أدخل طول العنبر وخلّي عدد الطيور يتحسب، وبعدين يظهر عرض المشتملات هنا.';
                            }

                            $broiler = static::isBroilerProject($get);
                            $offer = app(VentilationOffer::class)->calculate(
                                $birds,
                                $length,
                                $broiler,
                                $broiler ? $get('ventilation_side_fans') : null,
                                null,
                                [
                                    'heating' => $get('ventilation_heating_price'),
                                    'electricity' => $get('ventilation_electricity_price'),
                                    'monitor' => $get('ventilation_monitor_price'),
                                ],
                            );

                            return static::summaryRowsHtml('', $offer['rows']);
                        }),

                    Forms\Components\Toggle::make('include_construction')
                        ->label('العميل عاوز الإنشاءات')
                        ->helperText('عرض واحد. الرقم الأساسي من الإعدادات حسب ساندوتش بانل أو خرسانة، وتقدر تغيّره لهذا العرض.')
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('construction_choice')
                        ->label('نوع الإنشاءات')
                        ->options(fn (): array => ConstructionSummary::options())
                        ->default('sandwich')
                        ->native(false)
                        ->live()
                        ->required(fn (Get $get): bool => (bool) $get('include_construction'))
                        ->visible(fn (Get $get): bool => (bool) $get('include_construction'))
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $prices = ConstructionSummary::prices();
                            $set('construction_price', $prices[$state === 'cement' ? 'cement' : 'sandwich']);
                        }),

                    Forms\Components\TextInput::make('construction_price')
                        ->label('مبلغ الإنشاءات')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ج.م')
                        ->helperText('الرقم الأساسي من الإعدادات، وتقدر تغيّره لهذا العرض.')
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('include_construction'))
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, mixed $state, Get $get): void {
                            $prices = ConstructionSummary::prices();
                            $base = $prices[($get('construction_choice') === 'cement') ? 'cement' : 'sandwich'];
                            static::fillOfferBase($component, $state, $base);
                        }),

                    Forms\Components\Placeholder::make('construction_preview')
                        ->label('ملخص الإنشاءات')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => (bool) $get('include_construction'))
                        ->content(function (Get $get) {
                            $summary = app(ConstructionSummary::class)->chosen(
                                (string) ($get('construction_choice') ?: 'sandwich'),
                                null,
                                $get('construction_price'),
                            );

                            return static::summaryRowsHtml('', $summary['rows']);
                        }),
                ])
                ->columns(['default' => 1, 'sm' => 2]),
        ];
    }

    /** @param  \Closure  $live */
    protected static function equipmentStepSchema(\Closure $live): array
    {
        return [
            Forms\Components\Section::make('④ المعدات (اختياري)')
                ->description('نفس المواصفات للتسمين والبياض — القيم الافتراضية جاهزة')
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsed()
                ->schema([
                    Forms\Components\Placeholder::make('auto_exit_manure_count')
                        ->label('عدد مواتير دولاب السبلة')
                        ->visible(fn (Get $get): bool => static::isAutoExitProject($get))
                        ->content(function (Get $get): string {
                            $tiers = (int) ($get('tiers') ?: 0);

                            return $tiers > 0 ? $tiers.' ماتور = '.$tiers.' سيور' : '—';
                        }),

                    Forms\Components\Select::make('manure_motor_count_id')
                        ->label('عدد مواتير دولاب السبلة')
                        ->options(fn () => Lookup::options(Lookup::TYPE_MANURE_MOTOR_COUNT))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_MANURE_MOTOR_COUNT))
                        ->visible(fn (Get $get): bool => ! static::isAutoExitProject($get))
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('motor_power_id')
                        ->label('قدرة الماتور')
                        ->options(fn () => Lookup::options(Lookup::TYPE_MOTOR_POWER))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_MOTOR_POWER))
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('belts_per_line_id')
                        ->label('عدد السيور في الخط')
                        ->options(fn () => Lookup::options(Lookup::TYPE_BELTS_PER_LINE))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_BELTS_PER_LINE))
                        ->visible(fn (Get $get): bool => ! static::isAutoExitProject($get))
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('inner_belt_length_id')
                        ->label('طول السير الداخلي')
                        ->options(fn () => Lookup::options(Lookup::TYPE_INNER_BELT_LENGTH))
                        ->default(fn () => Lookup::query()->where('type', Lookup::TYPE_INNER_BELT_LENGTH)->where('code', '12')->value('id')
                            ?? Lookup::defaultId(Lookup::TYPE_INNER_BELT_LENGTH))
                        ->helperText('يتحدد من عرض العنبر: 12 متر من 10.5 حتى 13.5، و16 متر حتى 15.5، و20 متر حتى 18.')
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('outer_belt_length_id')
                        ->label('طول السير الخارجي')
                        ->options(fn () => Lookup::options(Lookup::TYPE_OUTER_BELT_LENGTH))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_OUTER_BELT_LENGTH))
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('heaters_count')
                        ->label('الدفايات')
                        ->options(HeaterOptions::selectOptions())
                        ->default(0)
                        ->visible(fn (Get $get) => (PoultryProjectType::tryFrom((string) ($get('project_type') ?: 'broiler'))?->isBroiler() ?? false)
                            && static::showsAccessoriesPreview($get))
                        ->native(false)
                        ->live()
                        ->afterStateUpdated($live),
                ])
                ->columns(['default' => 1, 'sm' => 2]),
        ];
    }

    protected static function summaryStepSchema(): array
    {
        return [
            Forms\Components\Section::make('الملخص المباشر')
                ->description('يتحدّث مع كل تعديل — احفظ عند الانتهاء')
                ->icon('heroicon-o-calculator')
                ->extraAttributes(['class' => 'pq-summary-sticky'])
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('bird_count')->label('الطيور')->readOnly(),
                        Forms\Components\TextInput::make('birds_per_nest')->label('طيور / عش')->readOnly(),
                        Forms\Components\TextInput::make('total_nests')->label('الأقفاص')->readOnly(),
                        Forms\Components\TextInput::make('subtotal')->label('الإجمالي ج.م')->readOnly(),
                    ]),
                    ...static::livePricingPreviewSchema(),
                    ...static::broilerWeightTableSchema(),
                    ...static::accessoriesPreviewTableSchema(),
                    Forms\Components\Placeholder::make('offer_files')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function ($livewire): HtmlString {
                            $record = $livewire->record ?? null;

                            return static::offerActionsHtml($record instanceof PoultryQuotation && $record->exists ? $record : null);
                        }),
                ]),
        ];
    }

    protected static function fillOfferBase(Forms\Components\TextInput $component, mixed $state, float $base): void
    {
        if ($state === null || $state === '') {
            $component->state($base);
        }
    }

    public static function offerActionsHtml(?PoultryQuotation $record): HtmlString
    {
        if ($record === null) {
            return new HtmlString('<p class="pq-offer-note">بعد الحفظ تظهر أزرار التحميل تحت الملخص.</p>');
        }

        $buttons = [
            [static::quoteDownloadLabel($record), route('poultry-quotations.pdf', $record), 'price'],
        ];
        if ($record->include_ventilation) {
            $buttons[] = ['تحميل عرض سعر المشتملات', route('poultry-quotations.accessories', $record), 'accessories'];
        }
        if ($record->include_construction) {
            $buttons[] = ['تحميل عرض سعر الإنشاءات', route('poultry-quotations.construction', $record), 'construction'];
        }

        $html = '<div class="pq-offer-actions">';
        foreach ($buttons as [$label, $url, $kind]) {
            $html .= '<a class="pq-offer-btn pq-offer-btn-'.$kind.'" href="'.e($url).'" target="_blank" rel="noopener">'.e($label).'</a>';
        }
        $html .= '</div>';

        return new HtmlString($html);
    }

    public static function quoteDownloadLabel(PoultryQuotation $record): string
    {
        return match (PoultryProjectType::tryFrom((string) $record->project_type)) {
            PoultryProjectType::Broiler => 'تحميل عرض سعر التسمين',
            PoultryProjectType::BroilerAutoExit => 'تحميل عرض سعر التسمين تخريج آلي',
            PoultryProjectType::Layer, PoultryProjectType::LayerAutoCollect, PoultryProjectType::LayerRearing => 'تحميل عرض سعر البياض',
            default => 'تحميل عرض السعر',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('رقم العرض')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('المندوب')
                    ->visible(fn (): bool => ! PoultryQuoteAccess::seesOwnQuotesOnly(auth()->user()))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('العميل')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('project_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (?string $state) => PoultryProjectType::tryFrom($state ?? '')?->labelAr() ?? $state),

                Tables\Columns\TextColumn::make('bird_count')
                    ->label('الطيور')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PoultryQuotation::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),

                Tables\Actions\Action::make('previewQuote')
                    ->label('معاينة عرض السعر')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('معاينة عرض السعر')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalWidth('7xl')
                    ->modalContent(fn (PoultryQuotation $record) => view('filament.poultry.quotation-preview', [
                        'previewUrl' => route('poultry-quotations.pdf', ['record' => $record, 'inline' => 1]),
                    ]))
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('downloadQuotePdf')
                            ->label(fn (PoultryQuotation $record): string => static::quoteDownloadLabel($record))
                            ->icon('heroicon-o-arrow-down-tray')
                            ->url(fn (PoultryQuotation $record) => route('poultry-quotations.pdf', $record))
                            ->openUrlInNewTab(),
                    ]),

                Tables\Actions\Action::make('downloadPdf')
                    ->label(fn (PoultryQuotation $record): string => static::quoteDownloadLabel($record))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn (PoultryQuotation $record) => route('poultry-quotations.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('downloadAccessories')
                    ->label('تحميل عرض سعر المشتملات')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn (PoultryQuotation $record): bool => (bool) $record->include_ventilation)
                    ->url(fn (PoultryQuotation $record) => route('poultry-quotations.accessories', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('downloadConstruction')
                    ->label('تحميل عرض سعر الإنشاءات')
                    ->icon('heroicon-o-building-office-2')
                    ->color('gray')
                    ->visible(fn (PoultryQuotation $record): bool => (bool) $record->include_construction)
                    ->url(fn (PoultryQuotation $record) => route('poultry-quotations.construction', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('generateCard')
                    ->label('توليد الكارت')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('توليد كارت السوشيال ميديا')
                    ->modalDescription('يُنشئ صورة الكارت التي تظهر مع رابط عرض السعر في رسالة الواتساب.')
                    ->action(function (PoultryQuotation $record) {
                        try {
                            $generator = app(PricingCardImageGenerator::class);
                            $path = $generator->generate($record);
                            $record->update(['image_path' => $path]);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('خطأ في توليد الكارت')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('تم توليد الكارت بنجاح')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('downloadCard')
                    ->label('تحميل الكارت')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (PoultryQuotation $record): bool => $record->image_path !== null)
                    ->action(function (PoultryQuotation $record) {
                        $path = str_replace('public/', '', $record->image_path);

                        if (! Storage::disk('public')->exists($path)) {
                            Notification::make()
                                ->title('الكارت غير موجود')
                                ->body('يرجى توليد الكارت أولاً.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

                        return response()->download(
                            Storage::disk('public')->path($path),
                            $record->quote_number.'-card.'.$ext
                        );
                    }),

                Tables\Actions\Action::make('shareWhatsApp')
                    ->label('واتساب')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('success')
                    ->url(fn (PoultryQuotation $record): string => $record->whatsapp_share_url)
                    ->openUrlInNewTab()
                    ->visible(fn (PoultryQuotation $record): bool => (float) $record->total > 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    public static function heightOptionsForType(string $type): array
    {
        $config = (new PoultryConfigLoader)->loadTechnicalConfig();
        $heights = $type === 'layer'
            ? ($config['layer_height_options'] ?? [3.5, 4.0, 4.5])
            : ($config['broiler_height_options'] ?? [3.7, 4.0, 4.5]);

        $options = [];
        foreach ($heights as $h) {
            $key = number_format((float) $h, 1);
            $options[$key] = $key.' م';
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPoultryQuotations::route('/'),
            'create' => Pages\CreatePoultryQuotation::route('/create'),
            'edit' => Pages\EditPoultryQuotation::route('/{record}/edit'),
            'view' => Pages\ViewPoultryQuotation::route('/{record}'),
        ];
    }
}
