<?php

namespace App\Filament\Resources;

use App\Enums\PoultryPricingScope;
use App\Enums\PoultryProjectType;
use App\Filament\Concerns\HasLivePoultryPricing;
use App\Filament\Resources\PoultryQuotationResource\Pages;
use App\Models\PoultryQuotation;
use App\Services\Pricing\PricingCardImageGenerator;
use App\Support\BroilerWeightReference;
use App\Support\HeaterOptions;
use Filament\Forms;
use Filament\Forms\Form;
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

    public static function form(Form $form): Form
    {
        $live = static::poultryPricingLiveCallback(false);

        return $form->schema([
            Forms\Components\Hidden::make('_init_live_calc')
                ->dehydrated(false)
                ->afterStateHydrated(fn (Forms\Set $set, Forms\Get $get) => static::refreshLivePoultryPricing($set, $get, false)),

            Forms\Components\Section::make('نوع المشروع والنطاق')
                ->schema([
                    Forms\Components\Select::make('project_type')
                        ->label('نوع العنبر')
                        ->options(PoultryProjectType::options())
                        ->default(PoultryProjectType::Broiler->value)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) use ($live) {
                            // reset height to the correct default for this project type
                            $set('height', $get('project_type') === 'layer' ? '3.5' : '3.7');
                            $live($set, $get);
                        }),

                    Forms\Components\Select::make('pricing_scope')
                        ->label('نطاق التسعير')
                        ->options(PoultryPricingScope::options())
                        ->default(PoultryPricingScope::FullProject->value)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated($live),
                ])
                ->columns(2),

            Forms\Components\Section::make('بيانات العميل')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('client_name')
                        ->label('اسم العميل')
                        ->required()
                        ->placeholder('مثال: أحمد نزار'),

                    Forms\Components\TextInput::make('client_phone')
                        ->label('رقم الهاتف')
                        ->placeholder('+2010xxxxxxx'),

                    Forms\Components\TextInput::make('client_address')
                        ->label('العنوان')
                        ->placeholder('المدينة / المحافظة'),
                ])
                ->columns(3),

            Forms\Components\Section::make('أبعاد العنبر')
                ->icon('heroicon-o-home')
                ->description('الحساب يتحدث فوراً عند تغيير أي قيمة')
                ->schema([
                    Forms\Components\Toggle::make('auto_lines_from_width')
                        ->label('اقتراح الخطوط من العرض (12م→4، 15م→5)')
                        ->default(true)
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated($live),

                    Forms\Components\TextInput::make('length')
                        ->label('الطول (م)')
                        ->required()
                        ->numeric()
                        ->default(81)
                        ->minValue(81)
                        ->helperText('الحد الأدنى 81م')
                        ->suffix('م')
                        ->live(debounce: 400)
                        ->afterStateUpdated($live),

                    Forms\Components\TextInput::make('width')
                        ->label('العرض (م)')
                        ->required()
                        ->numeric()
                        ->default(12)
                        ->minValue(1)
                        ->suffix('م')
                        ->live(debounce: 400)
                        ->afterStateUpdated($live),

                    Forms\Components\Select::make('height')
                        ->label('الارتفاع (م)')
                        ->required()
                        ->native(false)
                        ->options(fn (Forms\Get $get) => match ($get('project_type') ?? 'broiler') {
                            'layer' => ['3.5' => '3.5 م', '4.0' => '4.0 م', '4.5' => '4.5 م'],
                            default => ['3.7' => '3.7 م', '4.0' => '4.0 م', '4.5' => '4.5 م'],
                        })
                        ->default('3.7')
                        ->live()
                        ->afterStateUpdated($live),
                ])
                ->columns(3),

            Forms\Components\Section::make('منطقة الخدمات والوزن')
                ->schema([
                    Forms\Components\TextInput::make('service_length')
                        ->label('طول منطقة الخدمات (م)')
                        ->numeric()
                        ->default(10)
                        ->suffix('م')
                        ->helperText('تسمين: 9–10م | بياض: 7–9م')
                        ->live(debounce: 400)
                        ->afterStateUpdated($live),

                    Forms\Components\Select::make('bird_weight_kg')
                        ->label('وزن الطائر المستهدف (تسمين)')
                        ->options(BroilerWeightReference::selectOptions())
                        ->default('2.100')
                        ->visible(fn (Forms\Get $get) => ($get('project_type') ?? 'broiler') === 'broiler')
                        ->live()
                        ->afterStateUpdated($live),

                    ...static::broilerWeightTableSchema(),

                    Forms\Components\Select::make('wall_type')
                        ->label('نوع الحوائط')
                        ->options(['sandwich' => 'ساندوتش (1200)', 'cement' => 'خرسانة (2000)'])
                        ->default('sandwich')
                        ->live()
                        ->afterStateUpdated($live),
                ])
                ->columns(3),

            Forms\Components\Section::make('مواصفات البطاريات')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\TextInput::make('tiers')
                        ->label('عدد الأدوار')
                        ->required()
                        ->numeric()
                        ->integer()
                        ->default(4)
                        ->minValue(1)
                        ->maxValue(8)
                        ->live(debounce: 400)
                        ->afterStateUpdated($live),

                    Forms\Components\TextInput::make('lines')
                        ->label('عدد الخطوط')
                        ->required()
                        ->numeric()
                        ->integer()
                        ->default(4)
                        ->minValue(1)
                        ->maxValue(12)
                        ->live(debounce: 400)
                        ->afterStateUpdated($live),
                ])
                ->columns(2),

            Forms\Components\Section::make('المعدات الإضافية')
                ->icon('heroicon-o-cog')
                ->schema([
                    Forms\Components\TextInput::make('side_fans_count')
                        ->label('الشفاطات الجانبية')
                        ->numeric()
                        ->integer()
                        ->helperText('اتركه فارغاً للحساب التلقائي')
                        ->live(debounce: 400)
                        ->afterStateUpdated($live),

                    Forms\Components\Select::make('heaters_count')
                        ->label('الدفايات (اختياري)')
                        ->options(HeaterOptions::selectOptions())
                        ->default(0)
                        ->visible(fn (Forms\Get $get) => ($get('project_type') ?? 'broiler') === 'broiler')
                        ->live()
                        ->afterStateUpdated($live),
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => ($get('project_type') ?? 'broiler') === 'broiler'),

            Forms\Components\Section::make('ملخص الحساب المباشر')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Hidden::make('pricing_preview')->dehydrated(false),

                    Forms\Components\TextInput::make('bird_count')
                        ->label('عدد الطيور')
                        ->readOnly(),

                    Forms\Components\TextInput::make('birds_per_nest')
                        ->label('طيور / عش (حسب الوزن)')
                        ->readOnly(),

                    Forms\Components\TextInput::make('total_nests')
                        ->label('إجمالي الأعشاش')
                        ->readOnly(),

                    Forms\Components\TextInput::make('nests_per_line')
                        ->label('أعشاش / خط')
                        ->readOnly(),

                    Forms\Components\TextInput::make('back_fans_count')
                        ->label('المراوح')
                        ->readOnly(),

                    Forms\Components\TextInput::make('cooling_units')
                        ->label('التبريد (م)')
                        ->readOnly(),

                    Forms\Components\TextInput::make('windows_count')
                        ->label('الشبابيك')
                        ->readOnly(),

                    Forms\Components\TextInput::make('subtotal')
                        ->label('المجموع الفرعي (ج.م)')
                        ->readOnly(),

                    ...static::livePricingPreviewSchema(),
                ])
                ->columns(3),

            Forms\Components\Section::make('الضريبة')
                ->icon('heroicon-o-receipt-percent')
                ->schema([
                    Forms\Components\Select::make('vat_percentage')
                        ->label('نسبة ضريبة القيمة المضافة')
                        ->options([
                            0 => 'بدون ضريبة',
                            14 => '14% (مصر)',
                            15 => '15% (السعودية)',
                        ])
                        ->default(0)
                        ->native(false)
                        ->live(),
                ])
                ->columns(1),
        ]);
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
                    ->modalDescription('سيتم إنشاء صورة احترافية 1080×1080 للمشاركة على الواتساب والسوشيال ميديا.')
                    ->action(function (PoultryQuotation $record) {
                        try {
                            $generator = app(PricingCardImageGenerator::class);
                            $path = $generator->generate($record);
                            $record->update(['image_path' => $path]);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('خطأ في توليد الصورة')
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
                                ->title('الصورة غير موجودة')
                                ->body('يرجى توليد الكارت أولاً.')
                                ->danger()
                                ->send();

                            return;
                        }

                        return response()->download(
                            Storage::disk('public')->path($path),
                            $record->quote_number.'-card.png'
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
