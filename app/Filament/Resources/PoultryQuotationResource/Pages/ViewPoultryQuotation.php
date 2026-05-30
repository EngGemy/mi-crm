<?php

namespace App\Filament\Resources\PoultryQuotationResource\Pages;

use App\Filament\Resources\PoultryQuotationResource;
use App\Models\PoultryQuotation;
use App\Services\Pricing\PricingCardImageGenerator;
use Filament\Actions;
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
                    ->visible(fn (PoultryQuotation $record): bool => $record->image_path !== null)
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

                Components\Section::make('تفاصيل التكلفة')
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('concrete_cost')->label('الخرسانات')->money('EGP'),
                        Components\TextEntry::make('steel_cost')->label('الاستيل')->money('EGP'),
                        Components\TextEntry::make('walls_cost')->label('الحوائط')->money('EGP'),
                        Components\TextEntry::make('tanks_cost')->label('الخزانات')->money('EGP'),
                        Components\TextEntry::make('battery_cost')->label('البطاريات')->money('EGP'),
                        Components\TextEntry::make('back_fans_cost')->label('الشفاطات الخلفية')->money('EGP'),
                        Components\TextEntry::make('cooling_cost')->label('التبريد')->money('EGP'),
                        Components\TextEntry::make('windows_cost')->label('الشبابيك')->money('EGP'),
                        Components\TextEntry::make('side_fans_cost')->label('الشفاطات الجانبية')->money('EGP'),
                        Components\TextEntry::make('heaters_cost')->label('الدفايات')->money('EGP'),
                        Components\TextEntry::make('control_cost')->label('نظام التحكم')->money('EGP'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
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
                ->modalDescription('سيتم إنشاء صورة احترافية 1080×1080 للمشاركة على الواتساب والسوشيال ميديا.')
                ->action(function () {
                    try {
                        $generator = app(PricingCardImageGenerator::class);
                        $path = $generator->generate($this->record);
                        $this->record->update(['image_path' => $path]);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('خطأ في توليد الصورة')
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
                            ->title('الصورة غير موجودة')
                            ->body('يرجى توليد الكارت أولاً.')
                            ->danger()
                            ->send();

                        return;
                    }

                    return response()->download(
                        Storage::disk('public')->path($path),
                        $this->record->quote_number.'-card.png'
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
