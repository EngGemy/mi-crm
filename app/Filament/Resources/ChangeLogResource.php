<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChangeLogResource\Pages\ListChangeLogs;
use App\Models\ChangeLog;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractItem;
use App\Models\ContractMilestone;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadReminder;
use App\Models\Payment;
use App\Models\PoultryQuotation;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSection;
use App\Models\QuotationTerm;
use App\Models\Setting;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChangeLogResource extends Resource
{
    protected static ?string $model = ChangeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'سجل التدقيق';

    protected static ?string $modelLabel = 'سجل تدقيق';

    protected static ?string $pluralModelLabel = 'سجل التدقيق';

    protected static ?int $navigationSort = 120;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('event')
                    ->label('الإجراء')
                    ->formatStateUsing(fn ($state) => ChangeLog::EVENTS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        'signed', 'approved', 'paid', 'delivered' => 'success',
                        'cancelled' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        'login_failed' => 'danger',
                        'exported', 'imported', 'downloaded', 'converted' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => self::modelLabel($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('المُعرِّف')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('changed_fields')
                    ->label('الحقول المتغيّرة')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode('، ', $state) : $state)
                    ->wrap()
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('المستخدم')
                    ->searchable()
                    ->placeholder('النظام'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('الإجراء')
                    ->options(ChangeLog::EVENTS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('النوع')
                    ->options(self::subjectTypeOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('user_name')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->label('نطاق التاريخ')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))
                    )
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('من: '.$data['from'])->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('إلى: '.$data['until'])->removeField('until');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (ChangeLog $record) => 'تفاصيل السجل — '.(ChangeLog::EVENTS[$record->event] ?? $record->event))
                    ->modalContent(fn (ChangeLog $record) => view('filament.change-log.view-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('إغلاق')),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangeLogs::route('/'),
        ];
    }

    public static function modelLabel(string $class): string
    {
        return match ($class) {
            Contract::class => 'عقد',
            ContractItem::class => 'بند عقد',
            ContractMilestone::class => 'مرحلة عقد',
            ContractClause::class => 'شرط عقد',
            Quotation::class => 'عرض سعر',
            QuotationItem::class => 'بند عرض',
            PoultryQuotation::class => 'عرض دواجن',
            Customer::class => 'عميل',
            Payment::class => 'دفعة',
            Product::class => 'منتج',
            Lead::class => 'فرصة بيع',
            LeadActivity::class => 'نشاط فرصة',
            LeadReminder::class => 'تذكير',
            QuotationSection::class => 'قسم عرض',
            QuotationTerm::class => 'شرط عرض',
            Setting::class => 'إعداد',
            User::class => 'مستخدم',
            default => class_basename($class),
        };
    }

    protected static function subjectTypeOptions(): array
    {
        $classes = config('audit.models', []);
        $options = [];
        foreach ($classes as $class) {
            $options[$class] = self::modelLabel($class);
        }

        return $options;
    }
}
