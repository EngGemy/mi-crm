<?php

namespace App\Contracts;

interface WhatsAppGateway
{
    /** إرسال رسالة عبر API (returns true on success). */
    public function send(string $phone, string $message): bool;

    /** بناء رابط wa.me للإرسال اليدوي. */
    public function buildLink(string $phone, string $message): string;
}
