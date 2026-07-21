<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $via = [];

    public function via($notifiable)
    {
        return $this->via;
    }

    public function toArray($notifiable)
    {
        return [
            // Common notification data
        ];
    }

    protected function getNotificationType()
    {
        return class_basename(static::class);
    }
}
