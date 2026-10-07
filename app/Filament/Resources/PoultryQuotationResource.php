<?php

namespace App\Filament\Resources;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Filament\Concerns\HasLivePoultryPricing;
use App\Filament\Resources\PoultryQuotationResource\Pages;
use App\Models\Lookup;
use App\Models\PoultryQuotation;
use App\Services\Poultry\SavedClientCatalog;
use App\Services\Pricing\PricingCardImageGenerator;
use App\Services\Poultry\PoultryConfigLoader;
use App\Services\Poultry\PoultryQuoteAccess;
use Illuminate\Database\Eloquent\Builder;
use App\Support\BroilerWeightReference;
use App\Support\HeaterOptions;
use App\Support\LayerBaseLookups;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
        return auth()->user()?->hasAnyRole([
            'super_admin', 'admin', 'sales_manager', 'sales_rep',
        ]) ?? false;
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
                        ...static::layerSpecSchema($live),
                        ...static::equipmentStepSchema($live),
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
                        ->helperText('نفس الشخص لو متسجل في الجهتين يظهر مرة واحدة.')
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
                        ->required()->numeric()->step(0.1)
                        ->default('4')
                        ->minValue(0.1)->suffix('م')
                        ->live(onBlur: true)->afterStateUpdated($live),

                    Forms\Components\TextInput::make('lines')
                        ->label('الخطوط')
                        ->required()->numeric()->integer()->default(4)->minValue(1)->maxValue(12)
                        ->live(onBlur: true)->afterStateUpdated($live),

                    Forms\Components\Select::make('tiers')
                        ->label('الأدوار')
                        ->options([
                            3 => '3 أدوار',
                            4 => '4 أدوار',
                        ])
                        ->default(4)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $set('tiers', (int) $state);
                            static::refreshLivePoultryPricing($set, $get, false, (int) $state);
                        }),

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
                        ->live(),

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
                        ->default(fn () => round(2.8 * (float) settings('poultry_pricing.egp_to_usd_rate', 48), 2))
                        ->live(),

                    Forms\Components\TextInput::make('exchange_rate')
                        ->label('سعر الصرف')
                        ->numeric()
                        ->step(0.01)
                        ->suffix('ج/$')
                        ->default(fn () => settings('poultry_pricing.egp_to_usd_rate', 48))
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
                                ->visible(fn (Get $get) => static::isLayerProject($get) || static::showsWallTypeField($get))
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

    /** @param  \Closure  $live */
    protected static function layerSpecSchema(\Closure $live): array
    {
        $fields = [];
        foreach (LayerBaseLookups::fields() as $key => $field) {
            $type = $field['type'];
            $select = Forms\Components\Select::make('layer_specs.'.$key)
                ->label($field['label'])
                ->options(fn () => Lookup::options($type))
                ->default(fn () => Lookup::defaultId($type))
                ->native(false)
                ->searchable()
                ->live();

            if ($field['rate']) {
                $select->helperText(function (Get $get) use ($key, $type): string {
                    $specs = $get('layer_specs');
                    $base = LayerBaseLookups::numericOf(is_array($specs) ? ($specs[$key] ?? null) : null)
                        ?? LayerBaseLookups::defaultNumeric($type);
                    $rate = LayerBaseLookups::rateFromBase($base);

                    return 'القيمة الأساسية '.LayerBaseLookups::formatNumber($base).' — المعدل (النصف) '.LayerBaseLookups::formatNumber($rate);
                });
            } else {
                $select
                    ->helperText($field['calc'] === 'birds'
                        ? 'يدخل في سعة العنبر. الافتراضي 10، والبديل 9.'
                        : 'خصم منطقة الخدمات. الافتراضي 8 م².')
                    ->afterStateUpdated($live);
            }

            $fields[] = $select;
        }

        return [
            Forms\Components\Section::make('مواصفات البياض')
                ->description('تظهر مع إنتاج البياض. الأحمر في الورقة هو الافتراضي، والمعدل = نصف القيمة الأساسية. القوائم تتعدل من الإعدادات ← قوائم الخيارات.')
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (Get $get) => static::isLayerProject($get))
                ->schema($fields)
                ->columns(['default' => 1, 'sm' => 2]),
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

    /** @param  \Closure  $live */
    protected static function equipmentStepSchema(\Closure $live): array
    {
        return [
            Forms\Components\Section::make('④ المعدات (اختياري)')
                ->description('قيم افتراضية جاهزة — غيّرها فقط عند الحاجة')
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('manure_motor_count_id')
                        ->label('عدد مواتير دولاب السبلة')
                        ->options(fn () => Lookup::options(Lookup::TYPE_MANURE_MOTOR_COUNT))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_MANURE_MOTOR_COUNT))
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
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('inner_belt_length_id')
                        ->label('طول السير الداخلي')
                        ->options(fn () => Lookup::options(Lookup::TYPE_INNER_BELT_LENGTH))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_INNER_BELT_LENGTH))
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('outer_belt_length_id')
                        ->label('طول السير الخارجي')
                        ->options(fn () => Lookup::options(Lookup::TYPE_OUTER_BELT_LENGTH))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_OUTER_BELT_LENGTH))
                        ->native(false)->searchable()->live(),

                    Forms\Components\Select::make('silo_capacity_id')
                        ->label('سعة السايلو')
                        ->options(fn () => Lookup::options(Lookup::TYPE_SILO_CAPACITY))
                        ->default(fn () => Lookup::defaultId(Lookup::TYPE_SILO_CAPACITY))
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
                ]),
        ];
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
                            ->label('تحميل PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->url(fn (PoultryQuotation $record) => route('poultry-quotations.pdf', $record))
                            ->openUrlInNewTab(),
                    ]),

                Tables\Actions\Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn (PoultryQuotation $record) => route('poultry-quotations.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('generateCard')
                    ->label('توليد الكارت')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('توليد كارت السوشيال ميديا')
                    ->modalDescription('سيتم إنشاء كارت مشاركة احترافي (بدون الحاجة لـ Node.js على السيرفر).')
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
                    ->label('تحميل')
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
