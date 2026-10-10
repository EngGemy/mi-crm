<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LookupResource\Pages;
use App\Models\Lookup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class LookupResource extends Resource
{
    protected static ?string $model = Lookup::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'قوائم الخيارات';

    protected static ?string $modelLabel = 'خيار';

    protected static ?string $pluralModelLabel = 'قوائم الخيارات';

    protected static ?int $navigationSort = 95;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('الخيار')
                ->description('اكتب الاسم كما يظهر في عرض السعر، والقيمة التي تُستخدم في الحساب.')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('القائمة')
                        ->options(Lookup::typeLabels())
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('label_ar')
                        ->label('الاسم الظاهر')
                        ->placeholder('مثال: 25 طن')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('value')
                        ->label('القيمة')
                        ->placeholder('مثال: 25')
                        ->helperText('الرقم الذي يدخل في الحساب. مثال: 25 لسايلو 25 طن.')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label('يظهر في القوائم')
                        ->default(true)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('القيمة الافتراضية')
                ->description('الخيار الافتراضي هو الذي يُختار وحده عند فتح عرض سعر جديد، ويظهر نجمة بارزة في الجدول.')
                ->icon('heroicon-o-star')
                ->schema([
                    Forms\Components\Toggle::make('meta.is_default')
                        ->label('اجعل هذا الخيار هو الافتراضي')
                        ->onColor('warning')
                        ->inline(false)
                        ->helperText('لكل قائمة خيار افتراضي واحد. تفعيله هنا يلغي النجمة عن الخيار السابق في نفس القائمة.'),
                ]),
            Forms\Components\Section::make('تفاصيل إضافية')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('label_en')
                        ->label('الاسم بالإنجليزية'),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('ترتيب الظهور')
                        ->numeric()
                        ->default(0)
                        ->helperText('الرقم الأصغر يظهر أولاً.'),
                    Forms\Components\TextInput::make('code')
                        ->label('رمز داخلي')
                        ->maxLength(64)
                        ->helperText('يُكتب تلقائياً من القيمة. اتركه فارغاً عند الإضافة.')
                        ->dehydrated(fn ($state): bool => filled($state)),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->weight(fn (Lookup $record): FontWeight => static::isDefault($record) ? FontWeight::Bold : FontWeight::Medium)
                    ->description(fn (Lookup $record): ?string => static::isDefault($record) ? 'الخيار الافتراضي لهذه القائمة' : null),
                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('default_mark')
                    ->label('الافتراضي')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-star')
                    ->getStateUsing(fn (Lookup $record): ?string => static::isDefault($record) ? 'بارز' : null)
                    ->placeholder(''),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('ظاهر')
                    ->boolean(),
            ])
            ->recordClasses(fn (Lookup $record): ?string => static::isDefault($record) ? 'bg-warning-50 dark:bg-warning-400/10' : null)
            ->defaultSort('sort_order')
            ->defaultGroup(
                Group::make('type')
                    ->label('القائمة')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (Lookup $record): string => Lookup::typeLabels()[$record->type] ?? $record->type)
                    ->collapsible()
            )
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('القائمة')
                    ->options(Lookup::typeLabels()),
            ])
            ->actions([
                Tables\Actions\Action::make('makeDefault')
                    ->label('اجعلها الافتراضية')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Lookup $record): bool => ! static::isDefault($record))
                    ->action(function (Lookup $record): void {
                        $meta = $record->meta ?? [];
                        $meta['is_default'] = true;
                        $record->meta = $meta;
                        $record->save();
                    }),
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function isDefault(Lookup $record): bool
    {
        return (bool) data_get($record->meta, 'is_default');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLookups::route('/'),
        ];
    }
}
