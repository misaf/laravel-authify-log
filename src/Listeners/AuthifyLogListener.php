<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Listeners;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;
use Misaf\LaravelAuthifyLog\Notifications\LoginNotification;

class AuthifyLogListener
{
    public function handleAttempting(Attempting $event): void
    {
        $this->store(AuthifyLogActionEnum::Attempting, $event);
    }

    public function handleAuthenticated(Authenticated $event): void
    {
        $this->store(AuthifyLogActionEnum::Authenticated, $event);
    }

    public function handleCurrentDeviceLogout(CurrentDeviceLogout $event): void
    {
        $this->store(AuthifyLogActionEnum::CurrentDeviceLogout, $event);
    }

    public function handleFailed(Failed $event): void
    {
        $this->store(AuthifyLogActionEnum::Failed, $event);
    }

    public function handleLockout(Lockout $event): void
    {
        $this->store(AuthifyLogActionEnum::Lockout, $event);
    }

    public function handleLogin(Login $event): void
    {
        $user = $event->user ?? null;

        if ($user instanceof \Illuminate\Notifications\Notifiable && $user->hasVerifiedEmail()) {
            $user->notify(new LoginNotification());
        }

        $this->store(AuthifyLogActionEnum::Login, $event);
    }


    public function handleLogout(Logout $event): void
    {
        $this->store(AuthifyLogActionEnum::Logout, $event);
    }

    public function handleOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        $this->store(AuthifyLogActionEnum::OtherDeviceLogout, $event);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->store(AuthifyLogActionEnum::PasswordReset, $event);
    }

    public function handlePasswordResetLinkSent(PasswordResetLinkSent $event): void
    {
        $this->store(AuthifyLogActionEnum::PasswordResetLinkSent, $event);
    }

    public function handleRegistered(Registered $event): void
    {
        $this->store(AuthifyLogActionEnum::Registered, $event);
    }

    public function handleValidated(Validated $event): void
    {
        $this->store(AuthifyLogActionEnum::Validated, $event);
    }

    public function handleVerified(Verified $event): void
    {
        $this->store(AuthifyLogActionEnum::Verified, $event);
    }

    private function store(AuthifyLogActionEnum $action, object $event): void
    {
        // 1️⃣ Safely get the user if it exists on the event
        $user = property_exists($event, 'user') ? $event->user : null;

        // 2️⃣ Only call notify() if methods exist
        if ($user && is_object($user)) {
            if (method_exists($user, 'hasVerifiedEmail')
                && method_exists($user, 'notify')
                && $user->hasVerifiedEmail()
            ) {
                $user->notify(new LoginNotification());
            }
        }

        // 3️⃣ Safely get user ID
        $userId = is_object($user) && property_exists($user, 'id') ? $user->id : null;

        $timestamp = Carbon::now()->toDateTimeString();
        $logEntry = [
            'user_id'    => $userId,
            'action'     => $action->value,
            'ip_address' => request()->ip(),
            'ip_country' => request()->header('CF-IPCountry', 'XX'),
            'user_agent' => request()->userAgent(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        $authifyTransaction = Redis::connection('authify_log')->rpush('entries', json_encode($logEntry));
        Redis::connection('authify_log_channel')->publish('authify-log-channel', $authifyTransaction);
    }
}
