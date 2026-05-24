<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'إدارة العملاء';

    protected static ?string $navigationLabel = 'العملاء';

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('customers.view_any') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('كود العميل')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('يتم توليده تلقائياً')
                        ->visibleOn('edit'),

                    Forms\Components\Select::make('type')
                        ->label('نوع العميل')
                        ->options([
                            'individual' => 'فرد',
                            'company' => 'شركة',
                        ])
                        ->default('individual')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('national_id')
                        ->label('رقم الهوية / السجل المدني')
                        ->maxLength(50),

                    Forms\Components\Select::make('nationality')
                        ->label('الجنسية')
                        ->options([
                            'سعودي' => 'سعودي',
                            'مصري' => 'مصري',
                            'إماراتي' => 'إماراتي',
                            'كويتي' => 'كويتي',
                            'قطري' => 'قطري',
                            'بحريني' => 'بحريني',
                            'عماني' => 'عماني',
                            'أردني' => 'أردني',
                            'لبناني' => 'لبناني',
                            'سوري' => 'سوري',
                            'يمني' => 'يمني',
                            'عراقي' => 'عراقي',
                            'سوداني' => 'سوداني',
                            'ليبي' => 'ليبي',
                            'تونسي' => 'تونسي',
                            'جزائري' => 'جزائري',
                            'مغربي' => 'مغربي',
                            'فلسطيني' => 'فلسطيني',
                            'تركي' => 'تركي',
                            'أخرى' => 'أخرى',
                        ])
                        ->searchable()
                        ->default('سعودي')
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('معلومات التواصل')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('رقم الموبايل')
                        ->tel()
                        ->required()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('whatsapp')
                        ->label('رقم الواتساب')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('phone_alt')
                        ->label('رقم بديل')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->label('العنوان')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('city')
                        ->label('المدينة')
                        ->maxLength(100),

                    Forms\Components\Select::make('country')
                        ->label('الدولة')
                        ->options([
                            'EG' => 'مصر',
                            'SA' => 'السعودية',
                            'AE' => 'الإمارات',
                            'KW' => 'الكويت',
                            'QA' => 'قطر',
                            'BH' => 'البحرين',
                            'OM' => 'عُمان',
                            'JO' => 'الأردن',
                            'LB' => 'لبنان',
                            'IQ' => 'العراق',
                        ])
                        ->default('SA')
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('بيانات قانونية وضريبية')
                ->schema([
                    Forms\Components\TextInput::make('tax_number')
                        ->label('الرقم الضريبي')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('commercial_register')
                        ->label('السجل التجاري')
                        ->maxLength(50),

                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'نشط',
                            'inactive' => 'غير نشط',
                            'blacklisted' => 'محظور',
                        ])
                        ->default('active')
                        ->required(),
                ])->columns(3)->collapsed(),

            Forms\Components\Section::make('المرفقات والملاحظات')
                ->schema([
                    Forms\Components\FileUpload::make('attachments')
                        ->label('المرفقات (هوية، سجل تجاري، إلخ)')
                        ->multiple()
                        ->directory('customers')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('national_id')
                    ->label('رقم الهوية')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('الموبايل')
                    ->copyable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('city')
                    ->label('المدينة')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contracts_count')
                    ->label('عدد العقود')
                    ->counts('contracts')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_contracts_value')
                    ->label('إجمالي القيمة')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'blacklisted' => 'محظور',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'blacklisted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'blacklisted' => 'محظور',
                    ]),

                Tables\Filters\SelectFilter::make('nationality')
                    ->label('الجنسية')
                    ->options([
                        'سعودي' => 'سعودي',
                        'مصري' => 'مصري',
                        'إماراتي' => 'إماراتي',
                    ]),

                Tables\Filters\TrashedFilter::make()->label('المحذوفون'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
