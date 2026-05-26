<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExhibitionResource\Pages\CreateExhibition;
use App\Filament\Resources\ExhibitionResource\Pages\EditExhibition;
use App\Filament\Resources\ExhibitionResource\Pages\ListExhibitions;
use App\Filament\Resources\ExhibitionResource\Pages\ViewExhibition;
use App\Models\Exhibition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExhibitionResource extends Resource
{
    protected static ?string $model = Exhibition::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'المعارض';

    protected static ?string $modelLabel = 'معرض';

    protected static ?string $pluralModelLabel = 'المعارض';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('leads.view_any') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات المعرض')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المعرض')
                    ->required()
                    ->maxLength(200),

                Forms\Components\TextInput::make('location')
                    ->label('الموقع/المدينة'),

                Forms\Components\DatePicker::make('start_date')
                    ->label('تاريخ البداية')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('تاريخ النهاية'),

                Forms\Components\TextInput::make('cost')
                    ->label('التكلفة الإجمالية (ج.م)')
                    ->numeric()
                    ->prefix('ج.م')
                    ->default(0),

                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options(Exhibition::STATUSES)
                    ->default('planned')
                    ->required()
                    ->native(false),

                Forms\Components\Textarea::make('goal')
                    ->label('الهدف')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المعرض')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost')
                    ->label('التكلفة')
                    ->money('EGP')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => Exhibition::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'planned' => 'info',
                        'active' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Leads')
                    ->counts('leads')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Exhibition::STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExhibitions::route('/'),
            'create' => CreateExhibition::route('/create'),
            'view' => ViewExhibition::route('/{record}'),
            'edit' => EditExhibition::route('/{record}/edit'),
        ];
    }
}
