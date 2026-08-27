<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Misaf\LaravelAuthifyLog\Contracts\ResolvesUsers;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue(Config::string('authify-log.queue'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $username = app(ResolvesUsers::class)->name($notifiable);

        $message = new MailMessage()
            ->subject(__('authify-log::successfull-login-notification.login_notification'))
            ->greeting(__('authify-log::successfull-login-notification.hello_user', ['user' => $username]))
            ->line(__('authify-log::successfull-login-notification.we_noticed_that_your_account_was_accessed_on_our_website'))
            ->line(__('authify-log::successfull-login-notification.if_this_was_you_no_further_action_is_required'))
            ->line(__('authify-log::successfull-login-notification.if_this_was_not_you_please_reset_your_password_immediately_to_secure_your_account'));

        // The reset route belongs to the host application, which may not define
        // it at all; omit the button rather than throwing during delivery.
        $resetPasswordUrl = $this->resetPasswordUrl();

        if (null !== $resetPasswordUrl) {
            $message->action(
                __('authify-log::successfull-login-notification.reset_your_password'),
                $resetPasswordUrl,
            );
        }

        return $message
            ->line(__('authify-log::successfull-login-notification.thank_you_for_trusting_our_application'))
            ->salutation(
                __('authify-log::successfull-login-notification.best_regards') . "\n" . Config::string('app.name'),
            );
    }

    private function resetPasswordUrl(): ?string
    {
        $route = Config::get('authify-log.password_reset_route');

        if ( ! is_string($route) || '' === $route || ! Route::has($route)) {
            return null;
        }

        return route($route);
    }
}
