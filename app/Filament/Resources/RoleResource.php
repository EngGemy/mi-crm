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
                ->description('كل مجموعة تخص شاشة واحدة. «تحديد الكل» يطبَّق على المجموعة فقط.')
                ->schema(self::permissionGroupFields()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الدور')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => self::roleLabel($state))
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

    public static function roleLabel(string $name): string
    {
        return match ($name) {
            'super_admin' => 'مدير النظام',
            'admin' => 'إداري',
            'sales_manager' => 'مدير المبيعات',
            'sales_rep' => 'مندوب مبيعات',
            'accountant' => 'محاسب',
            default => $name,
        };
    }

    public static function roleDescription(string $name): string
    {
        return match ($name) {
            'super_admin' => 'كل الشاشات، المستخدمين، الأدوار، وإعدادات الشركة.',
            'admin' => 'إدارة التشغيل. لا يحذف المستخدمين ولا يغيّر إعدادات الشركة.',
            'sales_manager' => 'فريق المبيعات، العروض، المعرض، وتقرير المبيعات.',
            'sales_rep' => 'عملاؤه وعروضه فقط، بدون رؤية باقي المندوبين.',
            'accountant' => 'العروض والعقود والدفعات والتقرير المالي.',
            default => '',
        };
    }

    /** @return array<int, string> */
    public static function roleOptions(): array
    {
        $order = ['super_admin', 'admin', 'sales_manager', 'sales_rep', 'accountant'];
        $roles = Role::query()->get()->keyBy('name');
        $options = [];

        foreach ($order as $name) {
            if ($roles->has($name)) {
                $options[$roles[$name]->id] = self::roleLabel($name);
            }
        }

        foreach ($roles as $name => $role) {
            if (! array_key_exists($role->id, $options)) {
                $options[$role->id] = self::roleLabel($name);
            }
        }

        return $options;
    }

    /** @return array<int, string> */
    public static function roleDescriptionOptions(): array
    {
        $descriptions = [];
        foreach (Role::query()->get() as $role) {
            $descriptions[$role->id] = self::roleDescription($role->name);
        }

        return $descriptions;
    }

    /**
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    public static function permissionGroups(): array
    {
        $buckets = [];
        foreach (Permission::query()->orderBy('name')->get() as $permission) {
            [$resource, $action] = array_pad(explode('.', $permission->name, 2), 2, '');
            $buckets[$resource][] = ['id' => (string) $permission->id, 'action' => $action];
        }

        $resources = array_keys($buckets);
        usort($resources, fn (string $a, string $b) => self::resourceSort($a) <=> self::resourceSort($b));

        $grouped = [];
        foreach ($resources as $resource) {
            $items = $buckets[$resource];
            usort($items, fn (array $a, array $b) => self::actionSort($a['action']) <=> self::actionSort($b['action']));
            $options = [];
            foreach ($items as $item) {
                $options[$item['id']] = self::actionLabel($item['action']);
            }
            $grouped[$resource] = [
                'label' => self::resourceLabel($resource),
                'options' => $options,
            ];
        }

        return $grouped;
    }

    /** @return list<Forms\Components\CheckboxList> */
    public static function permissionGroupFields(): array
    {
        $fields = [];
        foreach (self::permissionGroups() as $resource => $group) {
            $optionIds = array_keys($group['options']);
            $fields[] = Forms\Components\CheckboxList::make('permission_group_'.$resource)
                ->label($group['label'])
                ->options($group['options'])
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->bulkToggleable()
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Model $record) use ($optionIds): void {
                    $selected = $record?->permissions?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
                    $component->state(array_values(array_intersect($selected, $optionIds)));
                });
        }

        return $fields;
    }

    public static function syncPermissionGroups(Model $record, array $state): void
    {
        $ids = [];
        foreach (array_keys(self::permissionGroups()) as $resource) {
            foreach ((array) ($state['permission_group_'.$resource] ?? []) as $id) {
                $ids[] = (int) $id;
            }
        }

        $record->syncPermissions(array_values(array_unique($ids)));
    }

    public static function translatePermissionName(string $name): string
    {
        [$resource, $action] = array_pad(explode('.', $name, 2), 2, '');

        return self::resourceLabel($resource).' — '.self::actionLabel($action);
    }

    protected static function resourceLabel(string $resource): string
    {
        return match ($resource) {
            'quotations' => 'عروض الأسعار',
            'leads' => 'العملاء المحتملين',
            'contracts' => 'العقود',
            'customers' => 'العملاء',
            'payments' => 'الدفعات',
            'products' => 'المنتجات',
            'exhibitions' => 'المعارض',
            'reports' => 'التقارير',
            'users' => 'المستخدمين',
            'settings' => 'الإعدادات',
            'audit' => 'سجل التدقيق',
            default => $resource,
        };
    }

    protected static function actionLabel(string $action): string
    {
        return match ($action) {
            'view_any' => 'عرض الكل',
            'view' => 'عرض',
            'view_own' => 'عرض سجلاته فقط',
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'update_own' => 'تعديل سجلاته فقط',
            'delete' => 'حذف',
            'send' => 'إرسال',
            'approve' => 'اعتماد',
            'convert' => 'تحويل لعقد',
            'duplicate' => 'نسخ',
            'preview_pdf' => 'معاينة PDF',
            'download_pdf' => 'تحميل PDF',
            'assign_roles' => 'تعيين الأدوار',
            'view_sales' => 'تقرير المبيعات',
            'view_financial' => 'التقرير المالي',
            'view_operations' => 'تقرير التشغيل',
            default => $action,
        };
    }

    protected static function resourceSort(string $resource): int
    {
        $order = [
            'quotations', 'leads', 'contracts', 'customers', 'payments', 'products',
            'exhibitions', 'reports', 'users', 'settings', 'audit',
        ];
        $index = array_search($resource, $order, true);

        return $index === false ? 100 : $index;
    }

    protected static function actionSort(string $action): int
    {
        $order = [
            'view_any', 'view', 'view_own', 'create', 'update', 'update_own', 'delete',
            'send', 'approve', 'convert', 'duplicate', 'preview_pdf', 'download_pdf',
            'assign_roles', 'view_sales', 'view_financial', 'view_operations',
        ];
        $index = array_search($action, $order, true);

        return $index === false ? 100 : $index;
    }
}
