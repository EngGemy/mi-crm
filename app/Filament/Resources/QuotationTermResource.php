<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationTermResource\Pages\CreateQuotationTerm;
use App\Filament\Resources\QuotationTermResource\Pages\EditQuotationTerm;
use App\Filament\Resources\QuotationTermResource\Pages\ListQuotationTerms;
use App\Models\QuotationTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuotationTermResource extends Resource
{
    protected static ?string $model = QuotationTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'عروض الأسعار';

    protected static ?string $navigationLabel = 'بنود العروض';

    protected static ?string $modelLabel = 'بند';

    protected static ?string $pluralModelLabel = 'بنود العروض';

    protected static ?int $navigationSort = 3;

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
                        ->placeholder('WARRANTY'),

                    Forms\Components\TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->required(),

                    Forms\Components\TextInput::make('title_en')
                        ->label('العنوان بالإنجليزية'),

                    Forms\Components\Toggle::make('is_required')
                        ->label('إلزامي')
                        ->default(false),

                    Forms\Components\Toggle::make('is_default')
                        ->label('افتراضي')
                        ->default(false)
                        ->helperText('يُضاف تلقائياً لكل عرض جديد'),

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
                                ->rows(12)
                                ->columnSpanFull()
                                ->helperText('استخدم {{variable_name}} للمتغيرات'),
                        ]),
                    Forms\Components\Tabs\Tab::make('English')
                        ->schema([
                            Forms\Components\Textarea::make('content_en')
                                ->label('Content in English')
                                ->rows(12)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\Section::make('المتغيرات')
                ->description('المتغيرات اللي بتتحط بين {{ }} في المحتوى')
                ->schema([
                    Forms\Components\Repeater::make('variables')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('اسم المتغير')
                                ->required()
                                ->placeholder('warranty_months'),
                            Forms\Components\TextInput::make('label')
                                ->label('الوصف')
                                ->required()
                                ->placeholder('شهور الضمان'),
                            Forms\Components\Select::make('type')
                                ->label('النوع')
                                ->options([
                                    'text' => 'نص',
                                    'number' => 'رقم',
                                    'boolean' => 'نعم/لا',
                                    'money' => 'مبلغ',
                                ])
                                ->default('text')
                                ->native(false),
                            Forms\Components\TextInput::make('default')
                                ->label('القيمة الافتراضية')
                                ->placeholder('12'),
                        ])
                        ->columns(4)
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('+ متغير'),
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

                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('إلزامي')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_required')->label('إلزامي'),
                Tables\Filters\TernaryFilter::make('is_default')->label('افتراضي'),
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
            'index' => ListQuotationTerms::route('/'),
            'create' => CreateQuotationTerm::route('/create'),
            'edit' => EditQuotationTerm::route('/{record}/edit'),
        ];
    }
}
