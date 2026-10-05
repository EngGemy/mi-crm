<?php

namespace App\Services\Poultry;

use App\Contracts\WhatsAppGateway;
use App\Models\PoultryQuotation;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

/**
 * Welcome WhatsApp for a new price-calculator quote.
 * The wording lives in settings (poultry.whatsapp_welcome) so it can be edited.
 */
class PoultryWelcomeWhatsApp
{
    public const DEFAULT_TEMPLATE = <<<'TXT'
السيد / {client_name}،

يسعدنا دعوتكم لزيارة جناح شركة MI Automatic Poultry Cages في معرض أجرينا الشرق الأوسط 2026، خلال الفترة من 15 إلى 17 أكتوبر 2026، بمركز القاهرة الدولي للمؤتمرات والمعارض — قاعة 1. يشرفنا حضوركم ونتطلع لاستقبالكم.

عرض السعر رقم {quote_number}
النوع: {project_type}
الأبعاد: {length} × {width} × {height} متر
الإجمالي: {total} ج.م

تحميل العرض: {pdf_url}
TXT;

    public function message(PoultryQuotation $quotation): string
    {
        $template = trim((string) settings('poultry.whatsapp_welcome', self::DEFAULT_TEMPLATE));
        if ($template === '') {
            $template = self::DEFAULT_TEMPLATE;
        }

        return strtr($template, [
            '{client_name}' => $quotation->client_name ?: 'العميل',
            '{quote_number}' => (string) ($quotation->quote_number ?: '—'),
            '{project_type}' => $quotation->project_type_label ?: '—',
            '{length}' => $this->amount($quotation->length),
            '{width}' => $this->amount($quotation->width),
            '{height}' => $this->amount($quotation->height),
            '{total}' => number_format((float) $quotation->total, 0),
            '{pdf_url}' => $this->pdfUrl($quotation),
        ]);
    }

    public function saveTemplate(string $template, ?int $userId = null): void
    {
        Setting::firstOrCreate(
            ['key' => 'poultry.whatsapp_welcome'],
            [
                'value' => self::DEFAULT_TEMPLATE,
                'type' => 'text',
                'category' => 'poultry',
                'label_ar' => 'نص رسالة الواتساب',
                'is_public' => false,
                'sort_order' => 1,
            ]
        );

        settings()->set('poultry.whatsapp_welcome', $template, $userId, 'نص واتساب المعرض');
    }

    public function link(PoultryQuotation $quotation): ?string
    {
        $phone = $this->normalizePhone((string) $quotation->client_phone);
        if ($phone === '') {
            return null;
        }

        return app(WhatsAppGateway::class)->buildLink($phone, $this->message($quotation));
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = '2'.$digits;
        }

        return $digits;
    }

    private function pdfUrl(PoultryQuotation $quotation): string
    {
        if (! $quotation->exists) {
            return '';
        }

        try {
            return URL::temporarySignedRoute(
                'poultry-quotations.welcome-pdf',
                now()->addDays(45),
                ['record' => $quotation->getKey()]
            );
        } catch (\Throwable) {
            return '';
        }
    }

    private function amount(mixed $value): string
    {
        $number = (float) $value;
        $formatted = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
