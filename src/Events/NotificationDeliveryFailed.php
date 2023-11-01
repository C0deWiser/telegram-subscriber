<?php

namespace Codewiser\Telegram\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Throwable;

class NotificationDeliveryFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string       $channel,
        public object       $notifiable,
        public Notification $notification,
        public Throwable    $exception
    )
    {
        //
    }
}
