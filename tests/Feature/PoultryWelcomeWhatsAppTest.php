<?php

namespace Tests\Feature;

use App\Models\PoultryQuotation;
use App\Models\User;
use App\Services\Poultry\PoultryWelcomeWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoultryWelcomeWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_message_uses_the_client_name_and_the_editable_template(): void
    {
        $quote = $this->quote('محمد جمال عبد التواب', '01012345678');

        $message = app(PoultryWelcomeWhatsApp::class)->message($quote);

        $this->assertStringContainsString('السيد / محمد جمال عبد التواب،', $message);
        $this->assertStringContainsString('معرض أجرينا الشرق الأوسط 2026', $message);
        $this->assertStringContainsString('welcome.pdf', $message);

        app(PoultryWelcomeWhatsApp::class)->saveTemplate('أهلاً {client_name} — عرض {quote_number}');

        $custom = app(PoultryWelcomeWhatsApp::class)->message($quote->fresh());

        $this->assertSame('أهلاً محمد جمال عبد التواب — عرض '.$quote->quote_number, $custom);
    }

    public function test_link_opens_that_clients_whatsapp_only(): void
    {
        $quote = $this->quote('محمد جمال عبد التواب', '01012345678');
        $other = $this->quote('عميل آخر', '01099999999');

        $link = app(PoultryWelcomeWhatsApp::class)->link($quote);

        $this->assertNotNull($link);
        $this->assertStringContainsString('https://wa.me/201012345678?text=', $link);
        $this->assertStringContainsString(urlencode('محمد جمال عبد التواب'), $link);
        $this->assertStringNotContainsString('201099999999', $link);
        $this->assertNotSame($link, app(PoultryWelcomeWhatsApp::class)->link($other));
    }

    public function test_missing_phone_does_not_build_a_link(): void
    {
        $quote = $this->quote('بدون رقم', '');

        $this->assertNull(app(PoultryWelcomeWhatsApp::class)->link($quote));
    }

    private function quote(string $name, string $phone): PoultryQuotation
    {
        return PoultryQuotation::withoutEvents(function () use ($name, $phone) {
            return PoultryQuotation::create([
                'quote_number' => 'Q-'.uniqid(),
                'client_name' => $name,
                'client_phone' => $phone,
                'project_type' => 'broiler',
                'length' => 72,
                'width' => 12,
                'height' => 3,
                'tiers' => 3,
                'lines' => 4,
                'total' => 1500000,
                'status' => 'draft',
                'created_by' => User::create([
                    'name' => 'مندوب',
                    'email' => uniqid('rep').'@example.com',
                    'password' => 'secret-secret',
                    'is_active' => true,
                ])->id,
            ]);
        });
    }
}
