<?php

namespace Codewiser\Telegram\Commands;

use Codewiser\Telegram\TelegramService;
use Telegram\Bot\Commands\Command;

class DeeplinkCommand extends Command
{
    protected string $name = 'start';

    protected string $pattern = '{token}';

    protected string $description = 'Start command to handshake with subscriber';

    public function __construct(public TelegramService $service)
    {
        //
    }

    public function handle(): void
    {
        $token = $this->argument('token');

        if ($token) {

            $notifiable = $this->service->provider->resolveToken($token);

            if ($notifiable) {
                $notifiable->setRouteForTelegram($this->update->message->from->id);

                $this->replyWithMessage(['text' => __('Welcome')]);
            } else {
                $this->replyWithMessage(['text' => __('Deeplink expired, lets try again :url', ['url' => url('/')])]);
            }
        } else {
            $this->replyWithMessage(['text' => __('Hello! Join us :url', ['url' => url('/')])]);
        }
    }
}
