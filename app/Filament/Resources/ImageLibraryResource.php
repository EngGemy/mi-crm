<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImageLibraryResource\Pages\CreateImageLibrary;
use App\Filament\Resources\ImageLibraryResource\Pages\EditImageLibrary;
use App\Filament\Resources\ImageLibraryResource\Pages\ListImageLibraries;
use App\Models\ImageLibrary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImageLibraryResource extends Resource
{
    protected static ?string $model = ImageLibrary::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'عروض الأسعار';

    protected static ?string $navigationLabel = 'مكتبة الصور';

    protected static ?string $modelLabel = 'صورة';

    protected static ?string $pluralModelLabel = 'مكتبة الصور';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('البيانات الأساسية')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('الكود')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->required(),

                    Forms\Components\TextInput::make('title_en')
                        ->label('العنوان بالإنجليزية'),

                    Forms\Components\Select::make('category')
                        ->label('التصنيف')
                        ->options(ImageLibrary::CATEGORIES)
                        ->required()
                        ->native(false),

                    Forms\Components\FileUpload::make('file_path')
                        ->label('الصورة')
                        ->directory('images/library')
                        ->image()
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('الوصف والوسوم')
                ->schema([
                    Forms\Components\TextInput::make('alt_text_ar')
                        ->label('نص بديل (عربي)'),

                    Forms\Components\TextInput::make('alt_text_en')
                        ->label('نص بديل (إنجليزي)'),

                    Forms\Components\TagsInput::make('tags')
                        ->label('الوسوم')
                        ->separator(',')
                        ->placeholder('أضف وسم واضغط Enter'),
                ])
                ->columns(2),

            Forms\Components\Section::make('البيانات الفنية')
                ->schema([
                    Forms\Components\TextInput::make('file_size')
                        ->label('حجم الملف (بايت)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('width')
                        ->label('العرض (بكسل)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('height')
                        ->label('الارتفاع (بكسل)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('usage_count')
                        ->label('عدد الاستخدامات')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(4)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('الصورة')
                    ->width(60)
                    ->height(60)
                    ->square(),

                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ImageLibrary::CATEGORIES[$state] ?? $state),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('الاستخدامات')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options(ImageLibrary::CATEGORIES)
                    ->multiple(),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImageLibraries::route('/'),
            'create' => CreateImageLibrary::route('/create'),
            'edit' => EditImageLibrary::route('/{record}/edit'),
        ];
    }
}
