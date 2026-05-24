<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Filament\Resources\LeadResource\Pages\EditLead;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Filament\Resources\LeadResource\Pages\ViewLead;
use App\Filament\Resources\LeadResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\RemindersRelationManager;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadReminder;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'العملاء المحتملين';

    protected static ?string $modelLabel = 'عميل محتمل';

    protected static ?string $pluralModelLabel = 'العملاء المحتملين';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('leads.view_any') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('بيانات الاتصال')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\Section::make()->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('الاسم')
                                ->required()
                                ->maxLength(200),
                            Forms\Components\TextInput::make('phone')
                                ->label('رقم الهاتف')
                                ->tel()
                                ->required(),
                            Forms\Components\TextInput::make('whatsapp')
                                ->label('رقم الواتساب')
                                ->tel(),
                            Forms\Components\TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->email(),
                            Forms\Components\TextInput::make('company')
                                ->label('الشركة/المزرعة'),
                            Forms\Components\TextInput::make('position')
                                ->label('المنصب'),
                        ])->columns(2),

                        Forms\Components\Section::make('الموقع')->schema([
                            Forms\Components\TextInput::make('country')
                                ->label('الدولة')
                                ->default('Egypt'),
                            Forms\Components\TextInput::make('city')
                                ->label('المدينة'),
                            Forms\Components\Textarea::make('address')
                                ->label('العنوان')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])->columns(2),
                    ]),

                Forms\Components\Wizard\Step::make('تفاصيل المشروع')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\Select::make('project_type')
                            ->label('نوع المشروع')
                            ->options([
                                'تسمين' => 'تسمين',
                                'بياض' => 'بياض',
                                'تربية' => 'تربية',
                                'أمهات' => 'أمهات',
                                'منشأة كاملة' => 'منشأة كاملة',
                                'مشتملات فقط' => 'مشتملات فقط',
                                'إنشاءات فقط' => 'إنشاءات فقط',
                                'صيانة' => 'صيانة',
                            ])
                            ->searchable()
                            ->native(false),
                        Forms\Components\TextInput::make('project_size')
                            ->label('حجم المشروع')
                            ->placeholder('مثلاً: 50,000 طائر'),
                        MoneyInput::make('estimated_budget')
                            ->label('الميزانية المتوقعة'),
                        Forms\Components\DatePicker::make('expected_close_date')
                            ->label('تاريخ الإغلاق المتوقع'),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('التصنيف')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\Select::make('source')
                            ->label('مصدر الـ Lead')
                            ->options(Lead::SOURCES)
                            ->required()
                            ->default('whatsapp')
                            ->native(false),
                        Forms\Components\TextInput::make('source_details')
                            ->label('تفاصيل المصدر')
                            ->placeholder('مثلاً: إعلان فيسبوك يناير 2026'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(Lead::STATUSES)
                            ->default('new')
                            ->required()
                            ->live()
                            ->native(false),
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options(Lead::PRIORITIES)
                            ->default('medium')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('score')
                            ->label('احتمال الإغلاق (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50)
                            ->suffix('%'),
                        Forms\Components\Select::make('assigned_to')
                            ->label('مسؤول المتابعة')
                            ->options(User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales_rep', 'sales_manager'])
                            )->pluck('name', 'id'))
                            ->searchable()
                            ->default(auth()->id())
                            ->native(false),
                        Forms\Components\DateTimePicker::make('next_followup_at')
                            ->label('موعد المتابعة القادم')
                            ->default(now()->addDays(2))
                            ->seconds(false),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('ملاحظات')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->label('تاجات')
                            ->placeholder('أضف تاج واضغط Enter')
                            ->columnSpanFull(),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lead_number')
                    ->label('الرقم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->description(fn ($record) => $record->company),

                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => Lead::STATUSES[$state] ?? $state)
                    ->colors([
                        'gray' => 'new',
                        'info' => 'contacted',
                        'warning' => 'qualified',
                        'primary' => 'opportunity',
                        'success' => 'won',
                        'danger' => 'lost',
                    ]),

                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->formatStateUsing(fn ($state) => Lead::SOURCES[$state] ?? $state),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 70 => 'success',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('estimated_budget')
                    ->label('الميزانية المتوقعة')
                    ->money('EGP')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('المسؤول')
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_followup_at')
                    ->label('المتابعة القادمة')
                    ->dateTime('Y-m-d H:i')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'
                    )
                    ->description(fn ($record) => $record->next_followup_at?->diffForHumans()
                    ),

                Tables\Columns\TextColumn::make('last_contact_at')
                    ->label('آخر تواصل')
                    ->dateTime('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Lead::STATUSES)
                    ->native(false),
                Tables\Filters\SelectFilter::make('source')
                    ->label('المصدر')
                    ->options(Lead::SOURCES)
                    ->native(false),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(Lead::PRIORITIES)
                    ->native(false),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('المسؤول')
                    ->options(User::pluck('name', 'id'))
                    ->native(false),
                Tables\Filters\Filter::make('hot_leads')
                    ->label('Leads ساخنة')
                    ->query(fn ($q) => $q->hotLeads()),
                Tables\Filters\Filter::make('needs_followup')
                    ->label('تحتاج متابعة')
                    ->query(fn ($q) => $q->needsFollowup()),
            ])
            ->actions([
                Tables\Actions\Action::make('add_activity')
                    ->label('+ نشاط')
                    ->icon('heroicon-o-plus-circle')
                    ->color('info')
                    ->form([
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
                    ])
                    ->action(function ($record, array $data) {
                        $record->activities()->create(array_merge($data, [
                            'user_id' => auth()->id(),
                            'is_completed' => true,
                            'completed_at' => now(),
                        ]));
                        Notification::make()
                            ->title('تم تسجيل النشاط')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('add_reminder')
                    ->label('+ تذكير')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->form([
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
                            ->default(now()->addDay())
                            ->seconds(false),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reminders()->create(array_merge($data, [
                            'user_id' => auth()->id(),
                            'status' => 'pending',
                        ]));
                        Notification::make()
                            ->title('تم إضافة التذكير')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('convert_to_customer')
                    ->label('تحويل لعميل')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['qualified', 'opportunity'])
                        && ! $record->customer_id
                    )
                    ->requiresConfirmation()
                    ->modalHeading('تحويل Lead إلى عميل')
                    ->modalDescription('سيتم إنشاء عميل جديد بنفس البيانات وربط الـ Lead به')
                    ->action(function ($record) {
                        $customer = Customer::create([
                            'name' => $record->name,
                            'phone' => $record->phone,
                            'email' => $record->email,
                            'address' => $record->address,
                            'created_by' => auth()->id(),
                        ]);

                        $record->update([
                            'customer_id' => $customer->id,
                            'status' => 'won',
                            'converted_at' => now(),
                        ]);

                        Notification::make()
                            ->title('تم التحويل بنجاح')
                            ->body("تم إنشاء عميل جديد: {$customer->name}")
                            ->success()
                            ->actions([
                                Action::make('view')
                                    ->label('عرض العميل')
                                    ->url(route('filament.admin.resources.customers.edit', $customer)),
                            ])
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_lost')
                    ->label('مفقود')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => ! in_array($record->status, ['won', 'lost']))
                    ->form([
                        Forms\Components\Select::make('lost_reason')
                            ->label('السبب')
                            ->options(Lead::LOST_REASONS)
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('lost_notes')
                            ->label('ملاحظات إضافية')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'lost',
                            'lost_at' => now(),
                            'lost_reason' => $data['lost_reason'],
                            'lost_notes' => $data['lost_notes'] ?? null,
                        ]);
                        Notification::make()
                            ->title('تم تسجيل الـ Lead كمفقود')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
            RemindersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('sales_rep')) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', ['new', 'contacted', 'qualified', 'opportunity'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}
