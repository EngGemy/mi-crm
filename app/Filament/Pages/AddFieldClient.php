<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Services\Poultry\ClientWelcomeWhatsApp;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

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
                            ->maxLength(200)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('phone')
                            ->label('الموبايل')
                            ->tel()
                            ->required()
                            ->maxLength(30)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('city')
                            ->label('المدينة')
                            ->maxLength(100)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('company')
                            ->label('المزرعة / الشركة')
                            ->maxLength(200)
                            ->live(onBlur: true),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('رسالة الترحيب')
                    ->description('تتفتح واتساب بالنص ده بعد التسجيل. عدّل النص قبل الإرسال، أو احفظه عشان يفضل للعملاء الجايين.')
                    ->schema([
                        Forms\Components\Toggle::make('send_welcome')
                            ->label('ابعت رسالة ترحيب على واتساب')
                            ->default(true)
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('welcome_message')
                            ->label('نص الرسالة')
                            ->rows(6)
                            ->required(fn (Get $get): bool => (bool) $get('send_welcome'))
                            ->helperText('المتغيرات: {client_name} الاسم، {phone} الموبايل، {city} المدينة، {company} المزرعة.')
                            ->visible(fn (Get $get): bool => (bool) $get('send_welcome'))
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('welcome_preview')
                            ->label('هتوصل للعميل كده')
                            ->visible(fn (Get $get): bool => (bool) $get('send_welcome'))
                            ->columnSpanFull()
                            ->content(function (Get $get): HtmlString {
                                $text = app(ClientWelcomeWhatsApp::class)->render(
                                    (string) $get('welcome_message'),
                                    (string) $get('name'),
                                    (string) $get('phone'),
                                    $get('city'),
                                    $get('company'),
                                );

                                return new HtmlString(
                                    '<div style="white-space:pre-wrap;background:#ffffff;color:#0f172a;color-scheme:light;border:1px solid #e2e8f0;border-radius:12px;padding:12px;line-height:1.7;">'
                                    .e($text)
                                    .'</div>'
                                );
                            }),
                    ]),
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
        $name = trim((string) $data['name']);
        Lead::create([
            'name' => $name,
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

        $sent = false;
        if ($data['send_welcome'] ?? false) {
            $welcome = app(ClientWelcomeWhatsApp::class);
            $text = $welcome->render(
                (string) ($data['welcome_message'] ?? ''),
                $name,
                $phone,
                $data['city'] ?? null,
                $data['company'] ?? null,
            );
            $link = $welcome->link($phone, $text);
            if ($link !== null) {
                $this->js('window.open('.json_encode($link).', "_blank")');
                $sent = true;
            }
        }

        Notification::make()
            ->title($sent ? 'تم تسجيل العميل وفتح واتساب' : 'تم تسجيل العميل')
            ->body($sent
                ? 'رسالة الترحيب جاهزة في واتساب. لو النافذة ما اتفتحتش، اسمح بالنوافذ المنبثقة وأعد الحفظ.'
                : 'من غير عرض سعر. أي سيلز يقدر يبحث عنه ويكمل العرض، وهيبان مين اللي أضافه.')
            ->success()
            ->send();

        $this->form->fill($this->blank());
    }

    public function saveWelcomeDefault(): void
    {
        $message = trim((string) ($this->data['welcome_message'] ?? ''));
        if ($message === '') {
            Notification::make()
                ->title('اكتب نص الرسالة الأول')
                ->warning()
                ->send();

            return;
        }

        app(ClientWelcomeWhatsApp::class)->saveTemplate($message, auth()->id());

        Notification::make()
            ->title('اتحفظ نص الترحيب')
            ->body('العملاء الجايين هيبدأوا بنفس النص، وتقدر تعدله قبل كل إرسال.')
            ->success()
            ->send();
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
            'send_welcome' => true,
            'welcome_message' => app(ClientWelcomeWhatsApp::class)->template(),
        ];
    }
}
