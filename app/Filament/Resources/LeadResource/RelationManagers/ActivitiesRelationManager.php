<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\LeadActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'النشاطات';

    protected static ?string $modelLabel = 'نشاط';

    protected static ?string $pluralModelLabel = 'النشاطات';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('نوع النشاط')
                ->options(LeadActivity::TYPES)
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('subject')
                ->label('الموضوع'),
            Forms\Components\Textarea::make('description')
                ->label('التفاصيل')
                ->rows(3),
            Forms\Components\Select::make('outcome')
                ->label('النتيجة')
                ->options(LeadActivity::OUTCOMES)
                ->native(false),
            Forms\Components\TextInput::make('duration_minutes')
                ->label('المدة (دقيقة)')
                ->numeric(),
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('مجدول لـ')
                ->seconds(false),
            Forms\Components\Toggle::make('is_completed')
                ->label('مكتمل')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => LeadActivity::TYPES[$state] ?? $state)
                    ->colors([
                        'primary' => 'call',
                        'success' => 'whatsapp',
                        'info' => 'email',
                        'warning' => 'visit',
                        'danger' => 'meeting',
                        'gray' => 'note',
                    ]),
                Tables\Columns\TextColumn::make('subject')
                    ->label('الموضوع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('بواسطة'),
                Tables\Columns\BadgeColumn::make('outcome')
                    ->label('النتيجة')
                    ->formatStateUsing(fn ($state) => LeadActivity::OUTCOMES[$state] ?? $state)
                    ->colors([
                        'success' => 'positive',
                        'warning' => 'neutral',
                        'danger' => 'negative',
                        'gray' => 'no_answer',
                        'info' => 'rescheduled',
                    ]),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('المدة')
                    ->suffix(' دقيقة'),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('مكتمل')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(LeadActivity::TYPES)
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة نشاط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
