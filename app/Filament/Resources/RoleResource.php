<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'الأدوار والصلاحيات';

    protected static ?string $modelLabel = 'دور';

    protected static ?string $pluralModelLabel = 'الأدوار';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 105;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الدور')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('اسم الدور (مفتاح)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit')
                        ->prefixIcon('heroicon-o-key')
                        ->helperText('مثال: sales_manager — بدون مسافات، بالإنجليزية'),

                    Forms\Components\TextInput::make('guard_name')
                        ->label('Guard')
                        ->default('web')
                        ->required()
                        ->disabledOn('edit'),
                ])->columns(2),

            Forms\Components\Section::make('الصلاحيات')
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->label('')
                        ->relationship('permissions', 'name')
                        ->options(function () {
                            return Permission::orderBy('name')->pluck('name', 'id')->mapWithKeys(
                                fn ($name, $id) => [$id => self::translatePermissionName($name)]
                            );
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(3)
                        ->gridDirection('row'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الدور')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => self::translateRoleName($state))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'sales_manager' => 'info',
                        'sales_rep' => 'success',
                        'accountant' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('عدد الصلاحيات')
                    ->counts('permissions'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('عدد المستخدمين')
                    ->counts('users'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record) {
                        if ($record->name === 'super_admin') {
                            return;
                        }
                    })
                    ->visible(fn (Role $record) => $record->name !== 'super_admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user?->hasAnyRole(['super_admin', 'admin'])) {
            return false;
        }
        // لا يمكن تعديل super_admin إلا من نفسه
        if ($record->name === 'super_admin' && ! $user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('super_admin') && $record->name !== 'super_admin';
    }

    protected static function translateRoleName(string $name): string
    {
        return match ($name) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'sales_manager' => 'مدير المبيعات',
            'sales_rep' => 'مندوب مبيعات',
            'accountant' => 'محاسب',
            default => $name,
        };
    }

    public static function translatePermissionName(string $name): string
    {
        $parts = explode('.', $name);
        $resource = $parts[0] ?? $name;
        $action = $parts[1] ?? '';

        $resourceLabels = [
            'quotations' => 'عروض الأسعار',
            'contracts' => 'العقود',
            'customers' => 'العملاء',
            'payments' => 'الدفعات',
            'products' => 'المنتجات',
            'settings' => 'الإعدادات',
            'reports' => 'التقارير',
            'users' => 'المستخدمين',
        ];

        $actionLabels = [
            'view_any' => 'عرض الكل',
            'view' => 'عرض',
            'view_own' => 'عرض خاص',
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'update_own' => 'تعديل خاص',
            'delete' => 'حذف',
            'send' => 'إرسال',
            'approve' => 'اعتماد',
            'convert' => 'تحويل',
            'duplicate' => 'نسخ',
            'assign_roles' => 'تعيين أدوار',
        ];

        $resourceLabel = $resourceLabels[$resource] ?? $resource;
        $actionLabel = $actionLabels[$action] ?? $action;

        return "{$resourceLabel} — {$actionLabel}";
    }
}
