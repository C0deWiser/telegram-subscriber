<?php

namespace Codewiser\Telegram\Listeners;

use Codewiser\Telegram\Contracts\TelegramNotifiable;
use GuzzleHttp\Exception\ClientException;
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

            if ($event->notifiable instanceof TelegramNotifiable) {

                // Laravel Notification Channel encapsulate ClientException into CouldNotSendNotification

                while ($exception && !($exception instanceof ClientException)) {
                    $exception = $exception->getPrevious();
                }

                if ($exception instanceof ClientException) {
                    $status = $exception->getResponse()->getStatusCode();
                    if ($status >= 400 && $status < 500) {
                        // Unsubscribe notifiable.
                        $event->notifiable->setRouteForTelegram(null);
                    }
                }
            }
        }
    }
}
