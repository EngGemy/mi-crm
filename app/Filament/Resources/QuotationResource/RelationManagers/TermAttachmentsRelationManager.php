<?php

namespace App\Filament\Resources\QuotationResource\RelationManagers;

use App\Models\QuotationTerm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TermAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'termAttachments';

    protected static ?string $title = 'البنود والشروط';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('quotation_term_id')
                ->label('البند')
                ->options(QuotationTerm::active()->pluck('title_ar', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_visible')
                ->label('ظاهر في PDF')
                ->default(true),

            Forms\Components\KeyValue::make('variables_values')
                ->label('قيم المتغيرات')
                ->keyLabel('المتغير')
                ->valueLabel('القيمة')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('content_override')
                ->label('تعديل النص (اختياري)')
                ->helperText('اتركه فارغاً لاستخدام النص الأصلي')
                ->rows(6)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('term.title_ar')
                    ->label('البند')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('ظاهر')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('+ بند جديد'),
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
}
