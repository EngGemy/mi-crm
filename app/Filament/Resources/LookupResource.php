<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LookupResource\Pages;
use App\Models\Lookup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
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
            Forms\Components\Select::make('type')
                ->label('نوع القائمة')
                ->options(Lookup::typeLabels())
                ->required()
                ->searchable()
                ->native(false),
            Forms\Components\TextInput::make('code')
                ->label('الكود')
                ->required()
                ->maxLength(64),
            Forms\Components\TextInput::make('label_ar')
                ->label('العنوان (عربي)')
                ->required(),
            Forms\Components\TextInput::make('label_en')
                ->label('العنوان (إنجليزي)'),
            Forms\Components\TextInput::make('value')
                ->label('القيمة'),
            Forms\Components\TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_active')
                ->label('مفعّل')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state) => Lookup::typeLabels()[$state] ?? $state)
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label_ar')->label('العنوان')->searchable(),
                Tables\Columns\TextColumn::make('value')->label('القيمة'),
                Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
            ])
            ->defaultSort('type')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(Lookup::typeLabels()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageLookups::route('/'),
        ];
    }
}
