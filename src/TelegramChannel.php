<?php

namespace Codewiser\Telegram;

use Codewiser\Telegram\Events\NotificationDeliveryFailed;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Traits\Localizable;
use Telegram\Bot\Objects\Message;
use Throwable;

/**
 * Telegram channel
 */
class TelegramChannel
{
    use Localizable;

    public function __construct(public TelegramService $service)
    {
        //
    }

    public function send(object $notifiable, Notification $notification): ?Message
    {
        $message = $this->withLocale(
            $notifiable instanceof HasLocalePreference ? $notifiable->preferredLocale() : null,
            fn() => method_exists($notification, 'toTelegram') ? $notification->toTelegram($notifiable) : null
        );

        $route = $notifiable->routeNotificationFor('telegram', $notification);

        if (is_null($message) || is_null($route)) {
            return null;
        }

        $payload = $this->prepareMessage($message);

        if (!$payload) {
            return null;
        }

        try {
            if ($message instanceof \Codewiser\Notifications\Messages\TelegramEditMessage) {
                return $this->service->bot()->editMessageText($payload + ['chat_id' => $route]);
            }

            return $this->service->bot()->sendMessage($payload + ['chat_id' => $route]);
        } catch (Throwable $exception) {
            event(new NotificationDeliveryFailed('telegram', $notifiable, $notification, $exception));
            logger()->debug($exception->getMessage());
            return null;
        }
    }

    protected function prepareMessage(mixed $message): ?array
    {
        if ($message instanceof Arrayable) {
            $message = $message->toArray();
        }

        if (is_string($message)) {
            $message = [
                'text' => $message
            ];
        }

        if (is_array($message)) {
            $message['parse_mode'] = $message['parse_mode'] ?? 'HTML';

            if ($message['parse_mode'] == 'MarkdownV2') {
                $message['parse_mode'] = 'HTML';
                $message['text'] = str($message['text'])->markdown()->toString();
            }

            $message['text'] = strip_tags($message['text'],
                '<b><strong><i><em><u><ins><s><strike><del><span><tg-spoiler><a><tg-emoji><code><pre><blockquote>'
            );

            return $message;
        }

        return null;
    }
}
