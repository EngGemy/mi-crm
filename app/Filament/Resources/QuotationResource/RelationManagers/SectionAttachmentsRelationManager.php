<?php

namespace App\Filament\Resources\QuotationResource\RelationManagers;

use App\Models\QuotationSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SectionAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'sectionAttachments';

    protected static ?string $title = 'الأقسام التقنية';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('quotation_section_id')
                ->label('القسم')
                ->options(QuotationSection::active()->pluck('title_ar', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),

            Forms\Components\Toggle::make('is_visible')
                ->label('ظاهر في PDF')
                ->default(true),

            Forms\Components\Textarea::make('content_override_ar')
                ->label('تعديل المحتوى (عربي)')
                ->helperText('اتركه فارغاً لاستخدام النص الأصلي')
                ->rows(6)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('content_override_en')
                ->label('تعديل المحتوى (إنجليزي)')
                ->helperText('Leave empty to use original text')
                ->rows(6)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section.title_ar')
                    ->label('القسم')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('section.category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn ($state) => QuotationSection::CATEGORIES[$state] ?? $state),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('ظاهر')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('+ قسم جديد'),
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
