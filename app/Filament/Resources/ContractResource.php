<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\ContractResource\Pages\CreateContract;
use App\Filament\Resources\ContractResource\Pages\EditContract;
use App\Filament\Resources\ContractResource\Pages\ListContracts;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractType;
use App\Models\Customer;
use App\Services\ContractCalculator;
use App\Services\ContractGenerator;
use App\Services\PaymentScheduler;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource العقود - الواجهة الرئيسية للنظام
 * يدعم:
 * - محرر بنود ديناميكي (drag & drop)
 * - حساب تلقائي للتكاليف
 * - توليد PDF بضغطة زر
 * - جدولة دفعات تلقائية
 */
class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'العقود والمشاريع';

    protected static ?string $navigationLabel = 'العقود';

    protected static ?string $modelLabel = 'عقد';

    protected static ?string $pluralModelLabel = 'العقود';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('contracts.view_any') ?? false;
    }

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
                        Forms\Components\Section::make('معلومات العقد')
                            ->schema([
                                Forms\Components\TextInput::make('contract_number')
                                    ->label('رقم العقد')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visibleOn('edit'),

                                Forms\Components\TextInput::make('project_code')
                                    ->label('كود المشروع')
                                    ->placeholder('PRJ-AN-2026')
                                    ->maxLength(50)
                                    ->helperText('يتم توليده تلقائياً، أو اكتب كود مخصص'),

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

                                Forms\Components\Select::make('contract_type_id')
                                    ->label('نوع العقد')
                                    ->relationship('contractType', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $type = ContractType::find($state);
                                        if ($type && $type->default_clauses) {
                                            // ممكن نُحمّل البنود الافتراضية تلقائياً
                                        }
                                    }),

                                Forms\Components\DatePicker::make('contract_date')
                                    ->label('تاريخ التعاقد')
                                    ->required()
                                    ->default(now())
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
                                    ->required()
                                    ->placeholder('الإسماعيلية - سرابيوم')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('أبعاد المشروع')
                            ->schema([
                                Forms\Components\TextInput::make('hall_length')
                                    ->label('طول العنبر (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('متر'),

                                Forms\Components\TextInput::make('hall_width')
                                    ->label('عرض العنبر (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('متر'),

                                Forms\Components\TextInput::make('hall_height')
                                    ->label('ارتفاع العنبر (م)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('متر'),

                                Forms\Components\TextInput::make('hall_count')
                                    ->label('عدد العنابر')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),

                                Forms\Components\TextInput::make('cage_count')
                                    ->label('عدد الأقفاص')
                                    ->numeric(),

                                Forms\Components\TextInput::make('bird_capacity')
                                    ->label('السعة (طائر)')
                                    ->numeric(),
                            ])
                            ->columns(3),
                    ]),

                // ====================================
                // Step 2: التكاليف
                // ====================================
                Forms\Components\Wizard\Step::make('التكاليف المالية')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Section::make('تكاليف الأقسام')
                            ->description('أدخل تكلفة كل قسم — الإجمالي يحسب تلقائياً')
                            ->schema([
                                MoneyInput::make('cages_cost')
                                    ->label('تكلفة البطاريات')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                MoneyInput::make('construction_cost')
                                    ->label('تكلفة الإنشاءات')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                MoneyInput::make('electricity_cost')
                                    ->label('تكلفة الكهرباء')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                MoneyInput::make('plumbing_cost')
                                    ->label('تكلفة السباكة')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                MoneyInput::make('accessories_cost')
                                    ->label('تكلفة المشتملات')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                MoneyInput::make('other_cost')
                                    ->label('تكاليف أخرى')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('الخصم والضريبة')
                            ->schema([
                                MoneyInput::make('discount_amount')
                                    ->label('قيمة الخصم')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('أو نسبة الخصم %')
                                    ->numeric()
                                    ->type('text')
                                    ->suffix('%')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                Forms\Components\TextInput::make('vat_percentage')
                                    ->label('نسبة الضريبة %')
                                    ->numeric()
                                    ->type('text')
                                    ->suffix('%')
                                    ->default(15)
                                    ->helperText('15% في السعودية، 14% في مصر')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => self::recalculate()),

                                Forms\Components\Select::make('currency')
                                    ->label('العملة')
                                    ->options([
                                        'EGP' => 'جنيه مصري (EGP)',
                                        'SAR' => 'ريال سعودي (SAR)',
                                        'USD' => 'دولار أمريكي (USD)',
                                        'AED' => 'درهم إماراتي (AED)',
                                    ])
                                    ->default('EGP')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn () => self::recalculate()),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('الإجمالي (محسوب مباشرة)')
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('الإجمالي قبل الخصم')
                                    ->content(fn (Forms\Get $get) => self::formatContractMoney($get, 'subtotal')),

                                Forms\Components\Placeholder::make('discount_live_display')
                                    ->label('الخصم')
                                    ->content(fn (Forms\Get $get) => self::formatContractMoney($get, 'discount_amount')),

                                Forms\Components\Placeholder::make('vat_live_display')
                                    ->label('الضريبة')
                                    ->content(fn (Forms\Get $get) => self::formatContractMoney($get, 'vat_amount')),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('الإجمالي النهائي')
                                    ->content(fn (Forms\Get $get) => self::formatContractMoney($get, 'total_value')),
                            ])
                            ->columns(2),
                    ]),

                // ====================================
                // Step 3: الجدول الزمني
                // ====================================
                Forms\Components\Wizard\Step::make('الجدول الزمني')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('manufacturing_days')
                                ->label('مدة التصنيع (يوم)')
                                ->numeric()
                                ->default(105)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $contractDate = $get('contract_date');
                                    if ($contractDate && $state) {
                                        $set('expected_delivery_date',
                                            Carbon::parse($contractDate)->addDays((int) $state)->format('Y-m-d'));
                                    }
                                }),

                            Forms\Components\DatePicker::make('expected_delivery_date')
                                ->label('تاريخ التسليم المتوقع')
                                ->displayFormat('Y-m-d')
                                ->helperText('يحسب تلقائياً'),

                            Forms\Components\DatePicker::make('actual_delivery_date')
                                ->label('تاريخ التسليم الفعلي')
                                ->displayFormat('Y-m-d'),

                            Forms\Components\TextInput::make('warranty_months')
                                ->label('ضمان عيوب التصنيع (شهر)')
                                ->numeric()
                                ->default(12)
                                ->suffix('شهر'),

                            Forms\Components\TextInput::make('manufacturing_warranty_years')
                                ->label('ضمان الصاج والسلك (سنة)')
                                ->numeric()
                                ->default(12)
                                ->suffix('سنة'),

                            Forms\Components\Select::make('status')
                                ->label('حالة العقد')
                                ->options(Contract::STATUSES)
                                ->default('draft')
                                ->required(),
                        ]),
                    ]),

                // ====================================
                // Step 4: البنود الاختيارية ⭐ الأهم
                // ====================================
                Forms\Components\Wizard\Step::make('بنود العقد')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Forms\Components\Repeater::make('clauseAttachments')
                            ->relationship()
                            ->label('بنود العقد المختارة')
                            ->schema([
                                Forms\Components\Select::make('contract_clause_id')
                                    ->label('البند')
                                    ->options(ContractClause::active()->pluck('title', 'id') ?? ContractClause::pluck('title', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\KeyValue::make('variables_values')
                                    ->label('قيم المتغيرات')
                                    ->keyLabel('المتغير')
                                    ->valueLabel('القيمة')
                                    ->columnSpanFull()
                                    ->helperText('املأ المتغيرات الموجودة في نص البند'),

                                Forms\Components\Repeater::make('items')
                                    ->label('بنود الجدول (لو موجود)')
                                    ->schema([
                                        Forms\Components\TextInput::make('description')
                                            ->label('الوصف'),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('الكمية')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('unit')
                                            ->label('الوحدة'),
                                        MoneyInput::make('unit_price')
                                            ->label('سعر الوحدة'),
                                        MoneyInput::make('total')
                                            ->label('الإجمالي'),
                                    ])
                                    ->columns(5)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state) => $state['description'] ?? 'بند')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('content_override')
                                    ->label('استبدال نص البند (اختياري)')
                                    ->helperText('اتركه فارغاً لاستخدام النص الأصلي')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_visible')
                                    ->label('ظاهر في العقد')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->orderColumn('sort_order')
                            ->addActionLabel('+ إضافة بند')
                            ->itemLabel(function (array $state) {
                                $clauseId = $state['contract_clause_id'] ?? null;
                                if ($clauseId) {
                                    $clause = ContractClause::find($clauseId);

                                    return $clause?->title ?? 'بند جديد';
                                }

                                return 'بند جديد';
                            }),
                    ]),

                // ====================================
                // Step 5: ملاحظات وتأكيد
                // ====================================
                Forms\Components\Wizard\Step::make('ملاحظات وتأكيد')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Textarea::make('custom_terms')
                            ->label('شروط إضافية مخصصة')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('ملاحظات داخلية (لا تظهر في العقد)')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('attachments')
                            ->label('مرفقات العقد')
                            ->multiple()
                            ->directory('contracts')
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('رقم العقد')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('project_code')
                    ->label('كود المشروع')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->wrap()
                    ->limit(30),

                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('النوع')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('contract_date')
                    ->label('تاريخ التعاقد')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('الإجمالي قبل الخصم')
                    ->money(fn (Contract $r) => $r->currency ?? 'EGP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vat_amount')
                    ->label('الضريبة')
                    ->money(fn (Contract $r) => $r->currency ?? 'EGP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('قيمة العقد')
                    ->money(fn (Contract $r) => $r->currency ?? 'EGP')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => (float) $state > 1_000_000 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('collection_percentage')
                    ->label('نسبة التحصيل')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1).'%')
                    ->color(fn ($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 70 => 'info',
                        $state >= 30 => 'warning',
                        default => 'danger',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('تاريخ التسليم')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn (Contract $r) => $r->is_delayed ? 'danger' : null),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Contract::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'on_hold' => 'warning',
                        'draft' => 'gray',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Contract::PAYMENT_STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'partially_paid' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Contract::STATUSES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(Contract::PAYMENT_STATUSES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('contract_type_id')
                    ->label('نوع العقد')
                    ->relationship('contractType', 'name')
                    ->multiple(),

                Tables\Filters\Filter::make('delayed')
                    ->label('متأخرة في التسليم')
                    ->query(fn ($query) => $query
                        ->where('expected_delivery_date', '<', now())
                        ->whereNotIn('status', ['completed', 'cancelled', 'archived'])),

                Tables\Filters\TrashedFilter::make()->label('المحذوفة'),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadPdf')
                    ->label('تحميل PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Contract $record) {
                        return app(ContractGenerator::class)->downloadPdf($record);
                    }),

                Tables\Actions\Action::make('viewPdf')
                    ->label('معاينة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->openUrlInNewTab()
                    ->url(fn (Contract $record) => route('contracts.preview', $record)),

                Tables\Actions\Action::make('generatePayments')
                    ->label('توليد جدول الدفعات')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('سيتم إنشاء جدول دفعات تلقائياً (70% / 25% / 5%) - الموجود سيُحذف.')
                    ->action(function (Contract $record) {
                        app(PaymentScheduler::class)->generateForContract($record);
                        Notification::make()
                            ->title('تم توليد جدول الدفعات بنجاح')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('contract_date', 'desc');
    }

    // ============= Helper Methods =============

    protected static function recalculate(): void
    {
        // العرض المباشر يعتمد على Placeholder + ContractCalculator؛ الحفظ النهائي في Model::saving()
    }

    /**
     * @return array{subtotal: float, discount_amount: float, vat_amount: float, total_value: float}
     */
    protected static function contractCalcFromGet(Forms\Get $get): array
    {
        return ContractCalculator::calculateContract([
            'cages_cost' => $get('cages_cost'),
            'construction_cost' => $get('construction_cost'),
            'electricity_cost' => $get('electricity_cost'),
            'plumbing_cost' => $get('plumbing_cost'),
            'accessories_cost' => $get('accessories_cost'),
            'other_cost' => $get('other_cost'),
            'discount_amount' => $get('discount_amount'),
            'discount_percentage' => $get('discount_percentage'),
            'vat_percentage' => $get('vat_percentage'),
        ]);
    }

    protected static function formatContractMoney(Forms\Get $get, string $key): string
    {
        $calc = self::contractCalcFromGet($get);
        $amount = (float) ($calc[$key] ?? 0);
        $cur = $get('currency') ?? 'EGP';

        return number_format($amount, 2).' '.$cur;
    }

    /**
     * تقدير الإجمالي من مصفوفة بيانات الفورم (للتأكيد قبل الحفظ).
     */
    public static function estimateTotalValueFromData(array $data): float
    {
        return ContractCalculator::calculateContract($data)['total_value'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'edit' => EditContract::route('/{record}/edit'),
        ];
    }
}
