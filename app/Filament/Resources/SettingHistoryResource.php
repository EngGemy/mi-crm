<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingHistoryResource\Pages;
use App\Models\SettingHistory;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingHistoryResource extends Resource
{
    protected static ?string $model = SettingHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'سجل تغييرات الإعدادات';

    protected static ?string $modelLabel = 'سجل تغيير';

    protected static ?string $pluralModelLabel = 'سجل التغييرات';

    protected static ?int $navigationSort = 110;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('setting.key')
                    ->label('المفتاح')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('old_value')
                    ->label('القيمة السابقة')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->wrap(),
                Tables\Columns\TextColumn::make('new_value')
                    ->label('القيمة الجديدة')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->wrap(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('سبب التعديل')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('changer.name')
                    ->label('المستخدم')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('setting_id')
                    ->label('الإعداد')
                    ->relationship('setting', 'key')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettingHistories::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('settings.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('settings.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
