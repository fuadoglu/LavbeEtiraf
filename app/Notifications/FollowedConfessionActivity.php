<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowedConfessionActivity extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferredLocale();
        $home = route('account.home', ['locale' => strtolower($locale)]);

        return (new MailMessage)
            ->subject(__('account.notification_subject', [], $locale))
            ->greeting(__('account.notification_greeting', [], $locale))
            ->line(__('account.notification_body', [], $locale))
            ->action(__('account.notification_action', [], $locale), $home)
            ->line(__('account.notification_footer', [], $locale));
    }
}
