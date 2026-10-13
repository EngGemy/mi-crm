<?php

namespace Tests\Feature;

use App\Services\Poultry\ClientWelcomeWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientWelcomeWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_uses_the_client_name_and_a_saved_custom_text(): void
    {
        $welcome = app(ClientWelcomeWhatsApp::class);

        $this->assertStringContainsString(
            'السيد / أحمد علي،',
            $welcome->render($welcome->template(), 'أحمد علي', '01012345678', 'المنصورة', 'مزرعة النور')
        );

        $welcome->saveTemplate('أهلاً {client_name} من {city} — {company}');

        $text = $welcome->render($welcome->template(), 'أحمد علي', '01012345678', 'المنصورة', 'مزرعة النور');

        $this->assertSame('أهلاً أحمد علي من المنصورة — مزرعة النور', $text);

        $link = $welcome->link('01012345678', $text);

        $this->assertNotNull($link);
        $this->assertStringContainsString('https://wa.me/201012345678?text=', $link);
        $this->assertStringContainsString(urlencode('أحمد علي'), $link);
    }

    public function test_missing_phone_does_not_build_a_link(): void
    {
        $this->assertNull(app(ClientWelcomeWhatsApp::class)->link('', 'أهلاً'));
    }
}
