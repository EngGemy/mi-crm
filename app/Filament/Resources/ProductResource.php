<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'المنتجات';

    protected static ?string $navigationLabel = 'كتالوج المنتجات';

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'المنتجات';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('products.view_any') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المنتج')->schema([
                Forms\Components\TextInput::make('code')
                    ->label('الكود')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),

                Forms\Components\TextInput::make('name')
                    ->label('اسم المنتج')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->maxLength(255),

                Forms\Components\Select::make('category')
                    ->label('الفئة')
                    ->options(Product::CATEGORIES)
                    ->required()
                    ->searchable()
                    ->native(false),

                Forms\Components\Select::make('unit')
                    ->label('الوحدة')
                    ->options(Product::UNITS)
                    ->required()
                    ->default('piece')
                    ->native(false),

                MoneyInput::make('standard_price')
                    ->label('السعر القياسي')
                    ->required(),

                Forms\Components\Select::make('currency')
                    ->label('العملة')
                    ->options([
                        'EGP' => 'جنيه مصري',
                        'SAR' => 'ريال سعودي',
                        'USD' => 'دولار',
                    ])
                    ->default('EGP')
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),

                Forms\Components\Textarea::make('technical_specs')
                    ->label('المواصفات الفنية')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('الفئة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Product::CATEGORIES[$state] ?? $state),

                Tables\Columns\TextColumn::make('unit')
                    ->label('الوحدة')
                    ->formatStateUsing(fn ($state) => Product::UNITS[$state] ?? $state),

                Tables\Columns\TextColumn::make('standard_price')
                    ->label('السعر')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('الفئة')
                    ->options(Product::CATEGORIES)
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')->label('نشط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('الفئة')
                    ->getTitleFromRecordUsing(fn (Product $r) => $r->category_label),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
