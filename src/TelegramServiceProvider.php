<?php

namespace Codewiser\Telegram;

use Codewiser\Telegram\Console\Commands\TelegramMeCommand;
use Codewiser\Telegram\Console\Commands\TelegramPollCommand;
use Codewiser\Telegram\Contracts\TelegramNotifiableProvider;
use Codewiser\Telegram\Listeners\UnsubscribeTelegramNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\BotsManager;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $commands = [
                TelegramMeCommand::class,
                TelegramPollCommand::class,
            ];

            $this->commands($commands);
        }

        Route::group([], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        });

        // Setup webhook_url for every configured bot
        foreach (config('telegram.bots', []) as $bot => $config) {
            if (isset($config['token'])) {
                config()->set("telegram.bots.$bot.webhook_url", url("telegram/$bot/{$config['token']}"));
            }
        }

        Event::listen(NotificationFailed::class, UnsubscribeTelegramNotifiable::class);
    }

    public function register(): void
    {
        $this->app->singleton(TelegramService::class, function () {
            $bot = config('telegram.default');

            $config = config("telegram.bots.$bot");

            return new TelegramService(
                $config['name'],
                app(BotsManager::class),
                app(TelegramNotifiableProvider::class)
            );
        });
    }
}
