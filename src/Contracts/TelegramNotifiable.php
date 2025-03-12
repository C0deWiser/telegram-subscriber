<?php

namespace Codewiser\Telegram\Contracts;

use Illuminate\Notifications\Notification;

interface TelegramNotifiable
{
    public function getKey();

    /**
     * @param  null|Notification  $notification
     *
     * @return mixed
     */
    public function routeNotificationForTelegram($notification = null): mixed;

    public function setRouteForTelegram($route);
}
