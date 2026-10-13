<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AddFieldClient extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'إضافة عميل جديد';

    protected static ?string $title = 'إضافة عميل جديد';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.add-field-client';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('leads.create') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill($this->blank());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات العميل')
                    ->description('تسجيل من بره من غير عرض سعر. أي مندوب يقدر يلاقيه بعد كده ويكمل عرض السعر.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\TextInput::make('phone')
                            ->label('الموبايل')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        Forms\Components\TextInput::make('city')
                            ->label('المدينة')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('company')
                            ->label('المزرعة / الشركة')
                            ->maxLength(200),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $phone = trim((string) $data['phone']);
        $existing = Lead::query()->with('creator:id,name')->where('phone', $phone)->first();

        if ($existing) {
            $who = $existing->creator?->name ?: 'مندوب';
            $when = $existing->created_at?->format('Y-m-d') ?: '';
            Notification::make()
                ->title('العميل متسجل قبل كده')
                ->body(trim("أضافه {$who} {$when}. ابحث عنه في حاسبة الأسعار وكمل عرض السعر."))
                ->warning()
                ->send();

            return;
        }

        $userId = auth()->id();
        Lead::create([
            'name' => trim((string) $data['name']),
            'phone' => $phone,
            'city' => $data['city'] ?? null,
            'company' => $data['company'] ?? null,
            'notes' => $data['notes'] ?? null,
            'country' => 'Egypt',
            'source' => 'walk_in',
            'status' => 'new',
            'priority' => 'medium',
            'created_by' => $userId,
            'assigned_to' => $userId,
        ]);

        Notification::make()
            ->title('تم تسجيل العميل')
            ->body('من غير عرض سعر. أي سيلز يقدر يبحث عنه ويكمل العرض، وهيبان مين اللي أضافه.')
            ->success()
            ->send();

        $this->form->fill($this->blank());
    }

    /** @return array<string, string|null> */
    private function blank(): array
    {
        return [
            'name' => null,
            'phone' => null,
            'city' => null,
            'company' => null,
            'notes' => null,
        ];
    }
}
