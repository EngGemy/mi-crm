<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\LeadReminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RemindersRelationManager extends RelationManager
{
    protected static string $relationship = 'reminders';

    protected static ?string $title = 'التذكيرات';

    protected static ?string $modelLabel = 'تذكير';

    protected static ?string $pluralModelLabel = 'التذكيرات';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('العنوان')
                ->required(),
            Forms\Components\Select::make('type')
                ->label('نوع التذكير')
                ->options(LeadReminder::TYPES)
                ->required()
                ->native(false),
            Forms\Components\DateTimePicker::make('remind_at')
                ->label('وقت التذكير')
                ->required()
                ->seconds(false),
            Forms\Components\Textarea::make('description')
                ->label('وصف')
                ->rows(3),
            Forms\Components\Select::make('status')
                ->label('الحالة')
                ->options(LeadReminder::STATUSES)
                ->default('pending')
                ->required()
                ->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => LeadReminder::TYPES[$state] ?? $state)
                    ->colors([
                        'primary' => 'call',
                        'success' => 'whatsapp',
                        'info' => 'email',
                        'warning' => 'visit',
                        'danger' => 'meeting',
                        'gray' => 'other',
                    ]),
                Tables\Columns\TextColumn::make('remind_at')
                    ->label('وقت التذكير')
                    ->dateTime('Y-m-d H:i')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => LeadReminder::STATUSES[$state] ?? $state)
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'info' => 'snoozed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('لـ'),
                Tables\Columns\IconColumn::make('notified')
                    ->label('تم الإشعار')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(LeadReminder::STATUSES)
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة تذكير'),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('إكمال')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'completed_by' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('remind_at', 'asc');
    }
}
