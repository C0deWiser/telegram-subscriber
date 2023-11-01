<?php

namespace Codewiser\Telegram\Contracts;

use Illuminate\Notifications\Notification;

interface TelegramNotifiable
{
    public function getKey();

    public function routeNotificationForTelegram(Notification $notification = null): mixed;

    public function setRouteForTelegram($route);
}
