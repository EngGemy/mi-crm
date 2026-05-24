<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationTypeResource\Pages\CreateQuotationType;
use App\Filament\Resources\QuotationTypeResource\Pages\EditQuotationType;
use App\Filament\Resources\QuotationTypeResource\Pages\ListQuotationTypes;
use App\Models\QuotationSection;
use App\Models\QuotationTerm;
use App\Models\QuotationType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotationTypeResource extends Resource
{
    protected static ?string $model = QuotationType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'عروض الأسعار';

    protected static ?string $navigationLabel = 'أنواع العروض';

    protected static ?string $modelLabel = 'نوع عرض';

    protected static ?string $pluralModelLabel = 'أنواع العروض';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('الكود')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('FULL_PROJECT'),

                    Forms\Components\TextInput::make('name')
                        ->label('الاسم بالعربية')
                        ->required(),

                    Forms\Components\TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية'),

                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('icon')
                        ->label('الأيقونة')
                        ->options([
                            'heroicon-o-home' => 'منزل',
                            'heroicon-o-cube' => 'صندوق',
                            'heroicon-o-wrench' => 'مفتاح',
                            'heroicon-o-cog-6-tooth' => 'ترس',
                            'heroicon-o-document-text' => 'وثيقة',
                        ])
                        ->default('heroicon-o-document-text')
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

                    Forms\Components\TextInput::make('default_validity_days')
                        ->label('مدة الصلاحية الافتراضية (يوم)')
                        ->numeric()
                        ->default(7)
                        ->suffix('يوم'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(3),

            Forms\Components\Section::make('الأقسام والبنود الافتراضية')
                ->schema([
                    Forms\Components\Select::make('default_sections')
                        ->label('الأقسام الافتراضية')
                        ->multiple()
                        ->options(fn () => QuotationSection::active()->pluck('title_ar', 'id'))
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('default_terms')
                        ->label('البنود الافتراضية')
                        ->multiple()
                        ->options(fn () => QuotationTerm::default()->pluck('title_ar', 'id'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            Forms\Components\Section::make('جدول الدفعات الافتراضي')
                ->schema([
                    Forms\Components\Repeater::make('default_payment_schedule')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('percentage')
                                ->label('النسبة %')
                                ->numeric()
                                ->required()
                                ->suffix('%'),
                            Forms\Components\TextInput::make('description')
                                ->label('الوصف')
                                ->placeholder('مثال: عند التعاقد'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('+ دفعة'),
                ])
                ->collapsible(),

            Forms\Components\Section::make('هيكل الـ PDF')
                ->schema([
                    Forms\Components\KeyValue::make('template_layout')
                        ->label('ترتيب صفحات العرض')
                        ->keyLabel('الصفحة')
                        ->valueLabel('المحتوى')
                        ->helperText('مثال: cover, about, sections, pricing, terms, ...'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('default_validity_days')
                    ->label('الصلاحية')
                    ->suffix(' يوم')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                Tables\Columns\TextColumn::make('quotations_count')
                    ->label('عدد العروض')
                    ->counts('quotations')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('نشط'),
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
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotationTypes::route('/'),
            'create' => CreateQuotationType::route('/create'),
            'edit' => EditQuotationType::route('/{record}/edit'),
        ];
    }
}
