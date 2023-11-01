<?php

namespace Codewiser\Telegram\Contracts;

interface TelegramNotifiableProvider
{
    /**
     * Generate deeplink token for the given notifiable.
     */
    public function generateToken(TelegramNotifiable $notifiable): string;

    /**
     * Find notifiable using deeplink token.
     */
    public function resolveToken(string $token): ?TelegramNotifiable;
}
