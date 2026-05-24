<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractClauseResource\Pages\CreateContractClause;
use App\Filament\Resources\ContractClauseResource\Pages\EditContractClause;
use App\Filament\Resources\ContractClauseResource\Pages\ListContractClauses;
use App\Models\ContractClause;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * إدارة مكتبة بنود العقود - يستطيع المستخدم إضافة/تعديل أي بند
 * هذا أهم Resource في النظام
 */
class ContractClauseResource extends Resource
{
    protected static ?string $model = ContractClause::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'العقود والمشاريع';

    protected static ?string $navigationLabel = 'مكتبة البنود';

    protected static ?string $modelLabel = 'بند';

    protected static ?string $pluralModelLabel = 'بنود العقود';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Tabs')
                ->tabs([
                    // ====================================
                    // Tab 1: بيانات البند الأساسية
                    // ====================================
                    Forms\Components\Tabs\Tab::make('البيانات الأساسية')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('كود البند')
                                    ->placeholder('CL-CONSTRUCTION')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('رمز فريد بالإنجليزية')
                                    ->maxLength(50),

                                Forms\Components\Select::make('category')
                                    ->label('الفئة')
                                    ->options(ContractClause::CATEGORIES)
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان البند بالعربية')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('title_en')
                                    ->label('عنوان البند بالإنجليزية')
                                    ->columnSpanFull()
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('description')
                                    ->label('وصف للموظف (لا يظهر في العقد)')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Toggle::make('is_required')
                                    ->label('بند إلزامي')
                                    ->helperText('لا يمكن حذفه من العقد')
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_default')
                                    ->label('يضاف افتراضياً')
                                    ->helperText('يُختار تلقائياً عند إنشاء العقد')
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true)
                                    ->inline(false),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('الترتيب')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Select::make('applicable_contract_types')
                                    ->label('أنواع العقود المطبق عليها')
                                    ->relationship('contracts', 'name')
                                    ->multiple()
                                    ->helperText('اتركه فارغاً للتطبيق على كل الأنواع')
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    // ====================================
                    // Tab 2: محتوى البند (Rich Editor)
                    // ====================================
                    Forms\Components\Tabs\Tab::make('محتوى البند')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\RichEditor::make('content')
                                ->label('النص')
                                ->required()
                                ->helperText('استخدم {{VAR_NAME}} للمتغيرات و [[ITEMS_TABLE]] لإدراج الجدول')
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike',
                                    'h2', 'h3', 'bulletList', 'orderedList',
                                    'undo', 'redo', 'link',
                                ])
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('content_en')
                                ->label('النص بالإنجليزية (اختياري)')
                                ->columnSpanFull(),
                        ]),

                    // ====================================
                    // Tab 3: المتغيرات الديناميكية
                    // ====================================
                    Forms\Components\Tabs\Tab::make('المتغيرات الديناميكية')
                        ->icon('heroicon-o-variable')
                        ->schema([
                            Forms\Components\Placeholder::make('vars_help')
                                ->label('')
                                ->content('عرّف المتغيرات التي يمكن للمستخدم تعديلها عند إضافة هذا البند للعقد. مثال: عدد سنوات الضمان، تكلفة بند معين، عدد العمال المطلوبين.')
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('variables')
                                ->label('المتغيرات')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('اسم المتغير (بالإنجليزية)')
                                        ->placeholder('warranty_years')
                                        ->required()
                                        ->regex('/^[a-zA-Z_][a-zA-Z0-9_]*$/')
                                        ->helperText('بدون مسافات أو رموز'),

                                    Forms\Components\TextInput::make('label')
                                        ->label('التسمية للمستخدم')
                                        ->placeholder('عدد سنوات الضمان')
                                        ->required(),

                                    Forms\Components\Select::make('type')
                                        ->label('نوع البيانات')
                                        ->options([
                                            'text' => 'نص',
                                            'number' => 'رقم',
                                            'money' => 'مبلغ مالي',
                                            'percentage' => 'نسبة %',
                                            'date' => 'تاريخ',
                                            'select' => 'قائمة منسدلة',
                                            'textarea' => 'نص طويل',
                                            'boolean' => 'نعم/لا',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Forms\Components\TextInput::make('default')
                                        ->label('القيمة الافتراضية')
                                        ->helperText('اختياري'),

                                    Forms\Components\Textarea::make('options')
                                        ->label('الخيارات (للقائمة المنسدلة)')
                                        ->placeholder('option1|التسمية1\noption2|التسمية2')
                                        ->visible(fn (Forms\Get $get) => $get('type') === 'select')
                                        ->rows(3),

                                    Forms\Components\Toggle::make('required')
                                        ->label('إلزامي')
                                        ->default(false),
                                ])
                                ->columns(2)
                                ->collapsible()
                                ->itemLabel(fn (array $state) => $state['label'] ?? $state['name'] ?? 'متغير')
                                ->defaultItems(0)
                                ->addActionLabel('+ متغير جديد')
                                ->columnSpanFull(),
                        ]),

                    // ====================================
                    // Tab 4: جدول داخلي (BOQ)
                    // ====================================
                    Forms\Components\Tabs\Tab::make('جدول البند (BOQ)')
                        ->icon('heroicon-o-table-cells')
                        ->schema([
                            Forms\Components\Placeholder::make('items_help')
                                ->label('')
                                ->content('لو البند يحتوي على جدول تفصيلي (مثل بنود الإنشاءات أو الكهرباء)، عرّف هنا الأعمدة. ستضع [[ITEMS_TABLE]] في النص لإدراجه.')
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('items_schema')
                                ->label('أعمدة الجدول')
                                ->schema([
                                    Forms\Components\TextInput::make('key')
                                        ->label('مفتاح العمود (إنجليزي)')
                                        ->placeholder('item_name')
                                        ->required(),

                                    Forms\Components\TextInput::make('label')
                                        ->label('عنوان العمود')
                                        ->placeholder('اسم البند')
                                        ->required(),

                                    Forms\Components\Select::make('type')
                                        ->label('نوع البيانات')
                                        ->options([
                                            'text' => 'نص',
                                            'number' => 'رقم',
                                            'money' => 'مبلغ',
                                            'date' => 'تاريخ',
                                        ])
                                        ->default('text'),

                                    Forms\Components\Toggle::make('sum_total')
                                        ->label('احسب المجموع')
                                        ->default(false)
                                        ->helperText('للأعمدة الرقمية فقط'),
                                ])
                                ->columns(2)
                                ->collapsible()
                                ->itemLabel(fn (array $state) => $state['label'] ?? $state['key'] ?? 'عمود')
                                ->defaultItems(0)
                                ->addActionLabel('+ عمود جديد')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('الفئة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ContractClause::CATEGORIES[$state] ?? $state)
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('إلزامي')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contracts_count')
                    ->label('عدد العقود المستخدم بها')
                    ->counts('contracts')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('الفئة')
                    ->options(ContractClause::CATEGORIES)
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')->label('نشط'),
                Tables\Filters\TernaryFilter::make('is_required')->label('إلزامي'),
                Tables\Filters\TernaryFilter::make('is_default')->label('افتراضي'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('duplicate')
                    ->label('نسخ')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (ContractClause $record) {
                        $copy = $record->replicate();
                        $copy->code = $record->code.'-COPY-'.now()->timestamp;
                        $copy->title = $record->title.' (نسخة)';
                        $copy->save();
                    }),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('الفئة')
                    ->getTitleFromRecordUsing(fn (ContractClause $r) => $r->category_label),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractClauses::route('/'),
            'create' => CreateContractClause::route('/create'),
            'edit' => EditContractClause::route('/{record}/edit'),
        ];
    }
}
