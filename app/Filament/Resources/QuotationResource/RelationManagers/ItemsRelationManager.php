<?php

namespace App\Filament\Resources\QuotationResource\RelationManagers;

use App\Filament\Forms\Components\MoneyInput;
use App\Models\QuotationSection;
use App\Services\QuotationCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'بنود التسعير';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('section_id')
                ->label('القسم المرتبط')
                ->options(QuotationSection::active()->pluck('title_ar', 'id'))
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('description_ar')
                ->label('البند (عربي)')
                ->required(),

            Forms\Components\TextInput::make('description_en')
                ->label('البند (إنجليزي)'),

            MoneyInput::make('unit_price')
                ->label('سعر الوحدة')
                ->default(0)
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateLineTotal($set, $get)),

            Forms\Components\TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->type('text')
                ->default(1)
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateLineTotal($set, $get)),

            Forms\Components\TextInput::make('unit')
                ->label('الوحدة')
                ->default('piece'),

            Forms\Components\TextInput::make('discount_percentage')
                ->label('خصم %')
                ->numeric()
                ->type('text')
                ->default(0)
                ->suffix('%')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::recalculateLineTotal($set, $get)),

            MoneyInput::make('total_price')
                ->label('الإجمالي')
                ->default(0)
                ->disabled()
                ->dehydrated(true),

            Forms\Components\Toggle::make('is_taxable')
                ->label('خاضع للضريبة')
                ->default(true),

            Forms\Components\TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),

            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description_ar')
                    ->label('البند')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('سعر الوحدة')
                    ->money('EGP')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('الوحدة'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('is_taxable')
                    ->label('ضريبة')
                    ->boolean(),
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

    protected static function recalculateLineTotal(Forms\Set $set, Forms\Get $get): void
    {
        $line = QuotationCalculator::calculateItemTotal([
            'unit_price' => $get('unit_price'),
            'quantity' => $get('quantity'),
            'discount_percentage' => $get('discount_percentage'),
        ]);
        $set('total_price', QuotationCalculator::formatDecimalString($line));
    }
}
