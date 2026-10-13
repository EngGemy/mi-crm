<?php

namespace App\Services\Poultry;

use App\Contracts\WhatsAppGateway;
use App\Models\Setting;

/**
 * رسالة ترحيب عند تسجيل عميل من غير عرض سعر. النص محفوظ ويتعدل.
 */
class ClientWelcomeWhatsApp
{
    public const DEFAULT_TEMPLATE = <<<'TXT'
السيد / {client_name}،

أهلاً بكم في شركة MI Automatic Poultry Cages. يسعدنا تواصلكم، وفريق المبيعات جاهز للرد على استفساراتكم وتجهيز عرض السعر المناسب لمزرعتكم.
TXT;

    public function template(): string
    {
        $saved = trim((string) settings('poultry.client_welcome', self::DEFAULT_TEMPLATE));

        return $saved !== '' ? $saved : self::DEFAULT_TEMPLATE;
    }

    public function render(string $template, string $name, ?string $phone = null, ?string $city = null, ?string $company = null): string
    {
        $template = trim($template) !== '' ? $template : $this->template();

        return trim(strtr($template, [
            '{client_name}' => trim($name) !== '' ? trim($name) : 'العميل',
            '{phone}' => trim((string) $phone),
            '{city}' => trim((string) $city),
            '{company}' => trim((string) $company),
        ]));
    }

    public function link(string $phone, string $message): ?string
    {
        $normalized = app(PoultryWelcomeWhatsApp::class)->normalizePhone($phone);
        if ($normalized === '' || trim($message) === '') {
            return null;
        }

        return app(WhatsAppGateway::class)->buildLink($normalized, $message);
    }

    public function saveTemplate(string $template, ?int $userId = null): void
    {
        $template = trim($template) !== '' ? trim($template) : self::DEFAULT_TEMPLATE;

        Setting::firstOrCreate(
            ['key' => 'poultry.client_welcome'],
            [
                'value' => self::DEFAULT_TEMPLATE,
                'type' => 'text',
                'category' => 'poultry',
                'label_ar' => 'رسالة ترحيب العميل الجديد',
                'is_public' => false,
                'sort_order' => 2,
            ]
        );

        settings()->set('poultry.client_welcome', $template, $userId, 'رسالة ترحيب العميل الجديد');
    }
}
