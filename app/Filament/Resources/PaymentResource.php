<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\PaymentResource\Pages\EditPayment;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'العقود والمشاريع';

    protected static ?string $navigationLabel = 'الدفعات';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'الدفعات';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('payments.view_any') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الدفعة')->schema([
                Forms\Components\Select::make('contract_id')
                    ->label('العقد')
                    ->relationship('contract', 'contract_number')
                    ->searchable()
                    ->required()
                    ->disabled()
                    ->visibleOn('edit'),

                Forms\Components\TextInput::make('payment_number')
                    ->label('رقم الدفعة')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('description')
                    ->label('الوصف')
                    ->required(),

                Forms\Components\TextInput::make('percentage')
                    ->label('النسبة %')
                    ->numeric()
                    ->type('text')
                    ->suffix('%')
                    ->disabled(),

                MoneyInput::make('expected_amount')
                    ->label('المبلغ المستحق')
                    ->disabled()
                    ->dehydrated(false),

                MoneyInput::make('paid_amount')
                    ->label('المبلغ المدفوع')
                    ->required()
                    ->live(onBlur: true),

                Forms\Components\DatePicker::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->required(),

                Forms\Components\DatePicker::make('paid_date')
                    ->label('تاريخ التحصيل'),

                Forms\Components\Select::make('payment_method')
                    ->label('وسيلة الدفع')
                    ->options(Payment::PAYMENT_METHODS)
                    ->native(false),

                Forms\Components\TextInput::make('reference_number')
                    ->label('رقم المرجع (التحويل/الشيك)')
                    ->maxLength(100),

                Forms\Components\TextInput::make('bank_name')
                    ->label('البنك'),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('attachments')
                    ->label('إيصالات / صور تحويل')
                    ->multiple()
                    ->directory('payments')
                    ->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('العقد')
                    ->searchable()
                    ->url(fn (Payment $r) => route('filament.admin.resources.contracts.edit', $r->contract_id)),

                Tables\Columns\TextColumn::make('contract.customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->wrap()
                    ->limit(25),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('النسبة')
                    ->formatStateUsing(fn ($state) => number_format($state, 0).'%')
                    ->badge(),

                Tables\Columns\TextColumn::make('expected_amount')
                    ->label('المستحق')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money('EGP')
                    ->color(fn (Payment $r) => (float) $r->paid_amount >= (float) $r->expected_amount ? 'success' : 'warning'
                    ),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn (Payment $r) => $r->is_overdue ? 'danger' : null),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('تاريخ التحصيل')
                    ->date('Y-m-d'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Payment::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'info',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(Payment::STATUSES)
                    ->multiple(),

                Tables\Filters\Filter::make('overdue')
                    ->label('متأخرة')
                    ->query(fn ($query) => $query
                        ->whereIn('status', ['pending', 'partial', 'overdue'])
                        ->where('due_date', '<', now())),

                Tables\Filters\Filter::make('due_soon')
                    ->label('مستحقة خلال 7 أيام')
                    ->query(fn ($query) => $query
                        ->whereIn('status', ['pending'])
                        ->whereBetween('due_date', [now(), now()->addDays(7)])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('due_date');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
