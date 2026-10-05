<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?string $navigationGroup = 'الإعدادات';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الحساب')
                ->description('الاسم ووسيلة الدخول. اترك كلمة المرور فارغة إذا لم ترد تغييرها.')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->prefixIcon('heroicon-o-user')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->prefixIcon('heroicon-o-envelope')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->prefixIcon('heroicon-o-phone')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->revealable()
                        ->prefixIcon('heroicon-o-lock-closed')
                        ->helperText(fn (string $context) => $context === 'edit'
                            ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.'
                            : 'سيستخدمها الموظف عند تسجيل الدخول.')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('الحساب نشط')
                        ->helperText('الحساب الموقوف لا يستطيع تسجيل الدخول.')
                        ->default(true)
                        ->inline(false)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('الدور')
                ->description('الدور يحدد الشاشات التي يراها الموظف. اختر دوراً واحداً لكل شخص.')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\CheckboxList::make('roles')
                        ->hiddenLabel()
                        ->relationship('roles', 'name')
                        ->options(fn () => RoleResource::roleOptions())
                        ->descriptions(fn () => RoleResource::roleDescriptionOptions())
                        ->columns(1)
                        ->required(),
                ]),

            Forms\Components\Section::make('صلاحيات إضافية')
                ->description('استثناء فوق الدور فقط. صلاحيات الدور نفسه تُدار من صفحة الأدوار والصلاحيات.')
                ->icon('heroicon-o-key')
                ->collapsed()
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->schema([
                    ...RoleResource::permissionGroupFields(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge()
                    ->formatStateUsing(fn ($state) => RoleResource::roleLabel((string) $state)),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('users.view_any') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
