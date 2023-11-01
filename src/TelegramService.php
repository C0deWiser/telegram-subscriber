<?php

namespace Codewiser\Telegram;

use Codewiser\Telegram\Contracts\TelegramNotifiable;
use Codewiser\Telegram\Contracts\TelegramNotifiableProvider;
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager;

/**
 * Telegram Service
 */
class TelegramService
{
    /**
     * @param string $name Bot name.
     * @param BotsManager $bots
     * @param TelegramNotifiableProvider $provider
     */
    public function __construct(
        public string                     $name,
        public BotsManager                $bots,
        public TelegramNotifiableProvider $provider
    )
    {
        //
    }

    public function bot(string $name = null): Api
    {
        return $this->bots->bot($name);
    }

    /**
     * Get Telegram deeplink for the given notifiable.
     */
    public function getDeeplink(TelegramNotifiable $notifiable): string
    {
        $token = $this->provider->generateToken($notifiable);

        return "https://telegram.me/$this->name?start=$token";
    }
}
