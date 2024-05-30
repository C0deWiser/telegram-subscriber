<?php

namespace Codewiser\Telegram\Listeners;

use Codewiser\Telegram\Contracts\TelegramNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;

class UnsubscribeTelegramNotifiable
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel == 'telegram') {
            $exception = $event->data['exception'] ?? null;

            if ($exception instanceof \Throwable && $event->notifiable instanceof TelegramNotifiable) {
                if ($exception->getCode() >= 400 && $exception->getCode() < 500) {
                    // Unsubscribe notifiable.
                    $event->notifiable->setRouteForTelegram(null);
                }
            }
        }
    }
}
