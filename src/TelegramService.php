<?php

namespace Codewiser\Telegram;

use Codewiser\Telegram\Contracts\TelegramNotifiable;
use Codewiser\Telegram\Contracts\TelegramNotifiableProvider;
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Exceptions\TelegramBotNotFoundException;
use Telegram\Bot\Exceptions\TelegramSDKException;

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

    /**
     * Get api by bot name (and optionally verify token).
     *
     * @throws TelegramBotNotFoundException|TelegramSDKException
     */
    public function bot(string $name = null, string $token = null): Api
    {
        $bot = $this->bots->bot($name);

        if ($token && $token != $bot->getAccessToken()) {
            throw new TelegramBotNotFoundException('Invalid token');
        }

        return $bot;
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
