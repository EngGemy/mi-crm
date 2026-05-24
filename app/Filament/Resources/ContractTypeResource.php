<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractTypeResource\Pages\CreateContractType;
use App\Filament\Resources\ContractTypeResource\Pages\EditContractType;
use App\Filament\Resources\ContractTypeResource\Pages\ListContractTypes;
use App\Models\ContractType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractTypeResource extends Resource
{
    protected static ?string $model = ContractType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'العقود والمشاريع';

    protected static ?string $navigationLabel = 'أنواع العقود';

    protected static ?string $modelLabel = 'نوع عقد';

    protected static ?string $pluralModelLabel = 'أنواع العقود';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')->schema([
                Forms\Components\TextInput::make('code')
                    ->label('الكود')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('FATTENING_FULL'),

                Forms\Components\TextInput::make('name')
                    ->label('الاسم بالعربية')
                    ->required(),

                Forms\Components\TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية'),

                Forms\Components\Select::make('icon')
                    ->label('الأيقونة')
                    ->options([
                        'heroicon-o-home' => 'منزل',
                        'heroicon-o-cube' => 'صندوق',
                        'heroicon-o-cog' => 'ترس',
                        'heroicon-o-bolt' => 'برق',
                        'heroicon-o-wrench' => 'مفتاح',
                    ])
                    ->default('heroicon-o-cube')
                    ->native(false),

                Forms\Components\Select::make('color')
                    ->label('اللون')
                    ->options([
                        'primary' => 'أزرق',
                        'success' => 'أخضر',
                        'warning' => 'أصفر',
                        'danger' => 'أحمر',
                        'info' => 'سماوي',
                        'gray' => 'رمادي',
                    ])
                    ->default('primary')
                    ->native(false),

                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),

                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(3),

            Forms\Components\Section::make('جدول الدفعات الافتراضي')
                ->description('الدفعات اللي هتُولّد تلقائياً عند إنشاء عقد من هذا النوع')
                ->schema([
                    Forms\Components\Repeater::make('payment_schedule_default')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('الوصف')
                                ->required(),
                            Forms\Components\TextInput::make('percentage')
                                ->label('النسبة %')
                                ->numeric()
                                ->required()
                                ->suffix('%'),
                            Forms\Components\Select::make('milestone_code')
                                ->label('المرحلة المرتبطة')
                                ->options([
                                    'CONTRACT_SIGN' => 'توقيع العقد',
                                    'MANUFACTURING_START' => 'بدء التصنيع',
                                    'SHIPPING_START' => 'بدء الشحن',
                                    'INSTALLATION_START' => 'بدء التركيب',
                                    'TESTING' => 'التشغيل التجريبي',
                                    'FINAL_DELIVERY' => 'التسليم النهائي',
                                ])
                                ->native(false),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('+ دفعة'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->badge(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->weight('bold')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('contracts_count')
                    ->label('عدد العقود')
                    ->counts('contracts')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractTypes::route('/'),
            'create' => CreateContractType::route('/create'),
            'edit' => EditContractType::route('/{record}/edit'),
        ];
    }
}
