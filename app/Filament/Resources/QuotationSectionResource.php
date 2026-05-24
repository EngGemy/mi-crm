<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationSectionResource\Pages\CreateQuotationSection;
use App\Filament\Resources\QuotationSectionResource\Pages\EditQuotationSection;
use App\Filament\Resources\QuotationSectionResource\Pages\ListQuotationSections;
use App\Models\ImageLibrary;
use App\Models\QuotationSection;
use App\Models\QuotationType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotationSectionResource extends Resource
{
    protected static ?string $model = QuotationSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'عروض الأسعار';

    protected static ?string $navigationLabel = 'أقسام العروض';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'أقسام العروض';

    protected static ?int $navigationSort = 4;

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
                        ->placeholder('STEEL_WORK'),

                    Forms\Components\TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->required(),

                    Forms\Components\TextInput::make('title_en')
                        ->label('العنوان بالإنجليزية'),

                    Forms\Components\Select::make('category')
                        ->label('التصنيف')
                        ->options(QuotationSection::CATEGORIES)
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(3),

            Forms\Components\Tabs::make('المحتوى')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('العربية')
                        ->schema([
                            Forms\Components\Textarea::make('content_ar')
                                ->label('المحتوى بالعربية')
                                ->rows(16)
                                ->columnSpanFull(),
                        ]),
                    Forms\Components\Tabs\Tab::make('English')
                        ->schema([
                            Forms\Components\Textarea::make('content_en')
                                ->label('Content in English')
                                ->rows(16)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\Section::make('الصور الافتراضية')
                ->schema([
                    Forms\Components\Select::make('default_images')
                        ->label('اختر من المكتبة')
                        ->multiple()
                        ->options(ImageLibrary::pluck('title_ar', 'id'))
                        ->searchable()
                        ->preload(),
                ]),

            Forms\Components\Section::make('أنواع العروض المطبقة')
                ->schema([
                    Forms\Components\Select::make('applicable_quotation_types')
                        ->label('ينطبق على')
                        ->multiple()
                        ->options(QuotationType::active()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('اتركه فارغاً للتطبيق على كل الأنواع'),
                ]),
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

                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn ($state) => QuotationSection::CATEGORIES[$state] ?? $state),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options(QuotationSection::CATEGORIES)
                    ->multiple(),
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
            'index' => ListQuotationSections::route('/'),
            'create' => CreateQuotationSection::route('/create'),
            'edit' => EditQuotationSection::route('/{record}/edit'),
        ];
    }
}
