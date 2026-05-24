<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

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
            Forms\Components\Section::make('بيانات المستخدم')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required(),
                    Forms\Components\TextInput::make('phone')
                        ->label('رقم الهاتف'),
                    Forms\Components\TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('الصلاحيات')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('الأدوار')
                        ->relationship('roles', 'name')
                        ->options([
                            'super_admin' => 'Super Admin',
                            'admin' => 'Admin',
                            'sales_manager' => 'مدير المبيعات',
                            'sales_rep' => 'مندوب مبيعات',
                            'accountant' => 'محاسب',
                        ])
                        ->multiple()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('permissions')
                        ->label('صلاحيات إضافية (مباشرة)')
                        ->relationship('permissions', 'name')
                        ->options(function () {
                            return Permission::orderBy('name')
                                ->pluck('name', 'id')
                                ->mapWithKeys(fn ($name, $id) => [
                                    $id => RoleResource::translatePermissionName($name),
                                ]);
                        })
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('صلاحيات مباشرة على المستخدم بدون ربطها بدور')
                        ->visible(fn () => auth()->user()?->hasRole('super_admin')),
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
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'sales_manager' => 'مدير المبيعات',
                        'sales_rep' => 'مندوب',
                        'accountant' => 'محاسب',
                        default => $state,
                    }),
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
