<?php

namespace App\Providers;

use App\Listeners\AuthEventListener;
use App\Models\QuotationItem;
use App\Observers\AuditObserver;
use App\Observers\QuotationItemObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);

        // المراقب الوظيفي لبنود العروض (إعادة حساب الإجماليات)
        QuotationItem::observe(QuotationItemObserver::class);

        // تسجيل أحداث المصادقة في سجل التدقيق
        $authListener = app(AuthEventListener::class);
        Event::listen(Login::class, [$authListener, 'handleLogin']);
        Event::listen(Logout::class, [$authListener, 'handleLogout']);
        Event::listen(Failed::class, [$authListener, 'handleFailed']);

        // المراقب العام للتدقيق — يُطبَّق على كل الموديلات المُدرجة في config/audit.php
        $auditObserver = app(AuditObserver::class);
        foreach (config('audit.models', []) as $modelClass) {
            $modelClass::observe($auditObserver);
        }

        // إجبار HTTPS في الإنتاج
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Blade directive: @setting('company.name_ar')
        Blade::directive('setting', function ($expression) {
            return "<?php echo e(settings($expression)); ?>";
        });

        // Blade directive: @rawsetting('html.content') — without escaping
        Blade::directive('rawsetting', function ($expression) {
            return "<?php echo settings($expression); ?>";
        });
    }
}
