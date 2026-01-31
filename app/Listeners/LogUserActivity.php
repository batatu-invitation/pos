<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogUserActivity
{
    public function onLogin(Login $event)
    {
        if ($event->user) {
            activity('auth')
                ->performedOn($event->user)
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'device' => request()->userAgent(),
                ])
                ->log('Logged in');
        }
    }

    public function onLogout(Logout $event)
    {
        if ($event->user) {
            activity('auth')
                ->performedOn($event->user)
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'device' => request()->userAgent(),
                ])
                ->log('Logged out');
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
        ];
    }
}
