<?php

namespace App\Services;

use App\Contracts\WhatsAppGateway;
use App\Mail\CrmAlertMail;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Mail;

/**
 * موجّه الإشعارات الموحّد — يقرأ الإعدادات ويوجّه لأنسب قناة.
 *
 * الأوضاع (crm.notifications.whatsapp_mode):
 *  - manual  → يعيد رابط wa.me فقط (الافتراضي، مجاني)
 *  - api     → يرسل عبر WhatsAppGateway::send()
 *  - hybrid  → التذكيرات الداخلية manual؛ الرسائل المهمة api
 */
class NotificationRouter
{
    public function __construct(
        protected WhatsAppGateway $whatsApp
    ) {}

    /**
     * إرسال إشعار لمستخدم.
     *
     * @param  User  $user  المستلم
     * @param  string  $title  عنوان الإشعار
     * @param  string  $body  نص الإشعار
     * @param  string  $context  السياق ('reminder'|'alert'|'system')
     * @param  string|null  $phone  رقم واتساب المستلم (إن كان خارجيًا)
     * @return array{bell: bool, email: bool, whatsapp: string|bool}
     */
    public function notify(
        User $user,
        string $title,
        string $body,
        string $context = 'reminder',
        ?string $phone = null,
    ): array {
        $results = ['bell' => false, 'email' => false, 'whatsapp' => false];

        // ١. الجرس الداخلي — دائمًا
        FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon('heroicon-o-bell')
            ->iconColor('warning')
            ->sendToDatabase($user);

        $results['bell'] = true;

        // ٢. البريد الإلكتروني — إذا مفعّل
        if (settings('crm.notifications.email_enabled', false) && $user->email) {
            try {
                Mail::to($user->email)->queue(
                    new CrmAlertMail($title, $body)
                );
                $results['email'] = true;
            } catch (\Throwable) {
            }
        }

        // ٣. واتساب — حسب الوضع
        $mode = settings('crm.notifications.whatsapp_mode', 'manual');
        $targetPhone = $phone ?? $user->phone ?? null;

        if ($targetPhone) {
            if ($mode === 'manual' || ($mode === 'hybrid' && $context === 'reminder')) {
                // أعِد رابط wa.me (المندوب يضغط للإرسال)
                $results['whatsapp'] = $this->whatsApp->buildLink($targetPhone, "{$title}\n{$body}");
            } elseif ($mode === 'api' || ($mode === 'hybrid' && $context !== 'reminder')) {
                $sent = $this->whatsApp->send($targetPhone, "{$title}\n{$body}");
                $results['whatsapp'] = $sent;
            }
        }

        return $results;
    }

    /** بناء رابط wa.me فقط (لأزرار الواتساب في الواجهة). */
    public function buildWhatsAppLink(string $phone, string $message): string
    {
        return $this->whatsApp->buildLink($phone, $message);
    }
}
