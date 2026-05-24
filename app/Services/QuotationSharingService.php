<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Facades\URL;

class QuotationSharingService
{
    /**
     * توليد رابط معاينة عام (signed)
     */
    public function getPublicPreviewUrl(Quotation $quotation, int $expiryDays = 30): string
    {
        return URL::temporarySignedRoute(
            'quotations.public',
            now()->addDays($expiryDays),
            ['quotation' => $quotation->quotation_number]
        );
    }

    /**
     * توليد رابط PDF عام (signed)
     */
    public function getPublicPdfUrl(Quotation $quotation, int $expiryDays = 30): string
    {
        return URL::temporarySignedRoute(
            'quotations.public.pdf',
            now()->addDays($expiryDays),
            ['quotation' => $quotation->quotation_number]
        );
    }

    /**
     * توليد رسالة WhatsApp جاهزة
     */
    public function getWhatsAppMessage(Quotation $quotation): string
    {
        $publicUrl = $this->getPublicPreviewUrl($quotation);
        $customerName = $quotation->customer->name;
        $total = number_format((float) $quotation->total_amount, 2);
        $currency = match ($quotation->currency) {
            'EGP' => 'ج.م',
            'USD' => '$',
            default => $quotation->currency,
        };
        $validUntil = $quotation->valid_until?->format('Y-m-d');
        $companyName = settings('company.name_ar', 'إم آي للصناعات المعدنية');

        $message = "السلام عليكم،\n\n";
        $message .= "السيد/ {$customerName}\n\n";
        $message .= "نرفق لكم عرض السعر الخاص بمشروعكم:\n";
        $message .= "رقم العرض: {$quotation->quotation_number}\n";
        $message .= "الإجمالي: {$total} {$currency}\n";
        $message .= "صالح حتى: {$validUntil}\n\n";
        $message .= "رابط العرض الكامل:\n{$publicUrl}\n\n";
        $message .= "نشكر لكم ثقتكم.\n";
        $message .= "{$companyName}";

        return $message;
    }

    /**
     * توليد رابط WhatsApp Click-to-Chat
     */
    public function getWhatsAppLink(Quotation $quotation, ?string $phone = null): string
    {
        $phone = $phone ?? $quotation->customer->phone;

        // تنظيف رقم الهاتف
        $phone = preg_replace('/[^\d]/', '', $phone);

        // إزالة الـ leading zero لو موجود وإضافة كود الدولة
        if (str_starts_with($phone, '0')) {
            $phone = '20'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '20') && ! str_starts_with($phone, '966')) {
            $phone = '20'.$phone;
        }

        $message = urlencode($this->getWhatsAppMessage($quotation));

        return "https://wa.me/{$phone}?text={$message}";
    }

    /**
     * توليد رابط WhatsApp بدون رقم محدد (يفتح للاختيار)
     */
    public function getWhatsAppShareLink(Quotation $quotation): string
    {
        $message = urlencode($this->getWhatsAppMessage($quotation));

        return "https://api.whatsapp.com/send?text={$message}";
    }
}
