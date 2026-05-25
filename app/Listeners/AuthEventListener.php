<?php

namespace App\Listeners;

use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthEventListener
{
    public function handleLogin(Login $event): void
    {
        AuditLogger::log($event->user, 'login', ['guard' => $event->guard]);
    }

    public function handleLogout(Logout $event): void
    {
        AuditLogger::log($event->user, 'logout', ['guard' => $event->guard]);
    }

    public function handleFailed(Failed $event): void
    {
        AuditLogger::log(null, 'login_failed', [
            'email' => $event->credentials['email'] ?? null,
            'guard' => $event->guard,
        ]);
    }
}
