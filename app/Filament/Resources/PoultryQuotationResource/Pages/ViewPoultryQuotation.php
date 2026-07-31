<?php

namespace App\Filament\Resources\PoultryQuotationResource\Pages;

use App\Filament\Resources\PoultryQuotationResource;
use App\Models\PoultryQuotation;
use App\Services\Pricing\PricingCardImageGenerator;
use App\Support\BroilerWeightReference;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewPoultryQuotation extends ViewRecord
{
    protected static string $resource = PoultryQuotationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('كارت المشاركة')
                    ->icon('heroicon-o-photo')
                    ->visible(fn (PoultryQuotation $record): bool => $record->image_path !== null
                        && str_ends_with(strtolower((string) $record->image_path), '.png'))
                    ->schema([
                        Components\ImageEntry::make('image_url')
                            ->label('')
                            ->width('100%')
                            ->height('auto')
                            ->extraImgAttributes([
                                'style' => 'max-width:100%;height:auto;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.15);',
                            ])
                            ->openUrlInNewTab(),
                    ]),

                Components\Section::make('بيانات العميل')
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('client_name')->label('اسم العميل'),
                        Components\TextEntry::make('client_phone')->label('الهاتف'),
                        Components\TextEntry::make('client_address')->label('العنوان'),
                    ]),

                Components\Section::make('النتيجة')
                    ->icon('heroicon-o-currency-pound')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('total')
                            ->label('الإجمالي النهائي')
                            ->money('EGP')
                            ->size(Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('primary'),

                        Components\TextEntry::make('subtotal')
                            ->label('المجموع الفرعي')
                            ->money('EGP'),

                        Components\TextEntry::make('vat_amount')
                            ->label('ضريبة القيمة المضافة')
                            ->money('EGP'),
                    ]),

                Components\Section::make('تفاصيل المشروع')
                    ->icon('heroicon-o-home')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('length')->label('الطول')->suffix(' م'),
                        Components\TextEntry::make('width')->label('العرض')->suffix(' م'),
                        Components\TextEntry::make('height')->label('الارتفاع')->suffix(' م'),
                        Components\TextEntry::make('tiers')->label('الأدوار'),
                        Components\TextEntry::make('lines')->label('الخطوط'),
                        Components\TextEntry::make('bird_count')->label('عدد الطيور'),
                    ]),

                Components\Section::make('جدول السعة وعدد الطيور')
                    ->icon('heroicon-o-table-cells')
                    ->description('الوزن المختار يتحكم في عدد الطيور لكل عش وإجمالي السعة — الملاحظة موضحة أدناه')
                    ->visible(fn (PoultryQuotation $record): bool => ($record->project_type ?? 'broiler') === 'broiler')
                    ->schema([
                        Components\TextEntry::make('bird_weight_kg')
                            ->label('وزن الطائر المستهدف')
                            ->suffix(' كجم')
                            ->weight('bold')
                            ->color('danger'),
                        Components\TextEntry::make('birds_per_nest')
                            ->label('طيور / عش')
                            ->weight('bold'),
                        Components\TextEntry::make('total_nests')
                            ->label('إجمالي الأعشاش'),
                        Components\TextEntry::make('bird_count')
                            ->label('السعة المعتمدة')
                            ->weight('bold')
                            ->color('primary')
                            ->suffix(' طائر'),
                        Components\ViewEntry::make('bird_capacity_panel')
                            ->label('')
                            ->columnSpanFull()
                            ->view('filament.poultry.bird-capacity-panel')
                            ->viewData(fn (PoultryQuotation $record): array => [
                                'record' => $record,
                            ]),
                    ])
                    ->columns(4),

                Components\Section::make('تفاصيل التكلفة')
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('concrete_cost')
                            ->label('الخرسانات')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('concrete'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('concrete') > 0),
                        Components\TextEntry::make('steel_cost')
                            ->label('الاستيل')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('steel'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('steel') > 0),
                        Components\TextEntry::make('walls_cost')
                            ->label('الحوائط')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('walls'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('walls') > 0),
                        Components\TextEntry::make('tanks_cost')
                            ->label('الخزانات')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('tanks'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('tanks') > 0),
                        Components\TextEntry::make('battery_cost')
                            ->label('البطاريات')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('battery'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('battery') > 0),
                        Components\TextEntry::make('heaters_cost')
                            ->label('الدفايات')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('heaters'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('heaters') > 0),
                        Components\TextEntry::make('control_cost')
                            ->label('جهاز المونيتر')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('control'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('control') > 0),
                        Components\TextEntry::make('electricity_cost')
                            ->label('الكهرباء والإنارة')
                            ->formatStateUsing(fn (PoultryQuotation $record) => $record->costForItemKey('electricity'))
                            ->money('EGP')
                            ->visible(fn (PoultryQuotation $record) => $record->costForItemKey('electricity') > 0),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changeBirdWeight')
                ->label('تغيير وزن الطائر')
                ->icon('heroicon-o-scale')
                ->color('danger')
                ->visible(fn (): bool => ($this->record->project_type ?? 'broiler') === 'broiler')
                ->modalHeading('التحكم في عدد الطيور عبر الوزن')
                ->modalDescription('اختيار وزن مختلف يغيّر عدد الطيور لكل عش، إجمالي السعة، وسعر البطاريات تلقائياً.')
                ->form([
                    Forms\Components\Select::make('bird_weight_kg')
                        ->label('وزن الطائر المستهدف')
                        ->options(BroilerWeightReference::selectOptions())
                        ->required()
                        ->default(fn () => (string) ($this->record->bird_weight_kg ?: '2.100'))
                        ->helperText('كل وزن = عدد طيور مختلف لكل عش حسب الجدول المعتمد'),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->bird_weight_kg = $data['bird_weight_kg'];
                        $this->record->birds_per_nest = null; // إعادة الحساب من خريطة الوزن
                        $this->record->autoCompute();
                        $this->record->save();

                        Notification::make()
                            ->title('تم تحديث السعة')
                            ->body('الوزن: '.$this->record->bird_weight_kg.' كجم — السعة: '.number_format((int) $this->record->bird_count).' طائر')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('تعذر تحديث السعة')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('downloadPdf')
                ->label('تحميل PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('poultry-quotations.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('generateCard')
                ->label('توليد كارت المشاركة')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('توليد كارت السوشيال ميديا')
                ->modalDescription('سيتم إنشاء كارت مشاركة احترافي (بدون الحاجة لـ Node.js على السيرفر).')
                ->action(function () {
                    try {
                        $generator = app(PricingCardImageGenerator::class);
                        $path = $generator->generate($this->record);
                        $this->record->update(['image_path' => $path]);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('خطأ في توليد الكارت')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('تم توليد الكارت بنجاح')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('downloadCard')
                ->label('تحميل الكارت')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (): bool => $this->record->image_path !== null)
                ->action(function () {
                    $path = str_replace('public/', '', $this->record->image_path);

                    if (! Storage::disk('public')->exists($path)) {
                        Notification::make()
                            ->title('الكارت غير موجود')
                            ->body('يرجى توليد الكارت أولاً.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

                    return response()->download(
                        Storage::disk('public')->path($path),
                        $this->record->quote_number.'-card.'.$ext
                    );
                }),

            Actions\Action::make('shareWhatsApp')
                ->label('مشاركة واتساب')
                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->color('success')
                ->url(fn (): string => $this->record->whatsapp_share_url)
                ->openUrlInNewTab()
                ->visible(fn (): bool => (float) $this->record->total > 0),

            Actions\EditAction::make()->label('تعديل'),

            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
