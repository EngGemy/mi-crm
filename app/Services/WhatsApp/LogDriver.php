<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Log;

/**
 * Log driver — يطبع الرسائل في الـ log بدلاً من إرسالها فعليًا.
 * يُستخدم كـ stub حتى يُربط مزوّد API حقيقي.
 */
class LogDriver implements WhatsAppGateway
{
    public function send(string $phone, string $message): bool
    {
        Log::channel('daily')->info('[WhatsApp Log Driver]', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    public function buildLink(string $phone, string $message): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        return 'https://wa.me/'.$normalized.'?text='.urlencode($message);
    }
}
