# Telegram Subscriber for Laravel

The package provides a solution for retrieving chat ID.

## Installation and setup

Package uses `irazasyed/telegram-bot-sdk`. So above all follow 
[Telegram Bot SDK 
instructions](https://telegram-bot-sdk.com/docs/getting-started/installation)
and set up your first Telegram Bot. Most likely you need to run:

    php artisan vendor:publish --tag="telegram-config"

In `config/telegram.php` configuration file add bot `name` parameter and 
register `DeeplinkCommand`. You may not define `webhook_url` as it will be 
reconfigured on a fly.

```php
# config/telegram.php

'bots' => [
    'my_bot' => [
        'name'             => env('TELEGRAM_BOT_NAME'),
        'token'            => env('TELEGRAM_BOT_TOKEN'),
        'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH'),
        //'webhook_url'      => env('TELEGRAM_WEBHOOK_URL'),
        'commands'         => [
            \Codewiser\Telegram\Commands\DeeplinkCommand::class
        ],
    ],
]
```

## Retrieving chat ID

Implement `\Codewiser\Telegram\Contracts\TelegramNotifiable` to a 
`User` model. You might need to write a migration...

```php
use \Illuminate\Notifications\Notifiable;
use \Illuminate\Database\Eloquent\Model;
use \Codewiser\Telegram\Contracts\TelegramNotifiable;

class User extends Model implements TelegramNotifiable
{
    use Notifiable;
    
    public function routeNotificationForTelegram($notification = null): mixed
    {
        return $this->telegram_user_id;
    }

    public function setRouteForTelegram($route): void
    {
        $this->telegram_user_id = $route;
        
        $this->save();
    }
}
```

Now, create service to implement 
`\Codewiser\Telegram\Contracts\TelegramNotifiableProvider`. This is an 
example of implementation, your may implement it however you like.

```php
use \Codewiser\Telegram\Contracts\TelegramNotifiableProvider;

class TelegramUserProvider implements TelegramNotifiableProvider
{
    /**
     * Issue and remember new token for a given notifiable.
     */
    public function generateToken(TelegramNotifiable $notifiable): string
    {
        $token = Str::random(40);

        cache()->set(
            $token,
            [
                'key'   => $notifiable->getKey(),
                'model' => get_class($notifiable),
            ],
            now()->addMinutes(5)
        );

        return $token;
    }
    
    /**
     * Find notifiable associated with a given token.
     */
    public function resolveToken(string $token): ?TelegramNotifiable
    {
        $notifiable = cache()->pull($token);

        if ($notifiable) {
            $key = $notifiable['key'];
            $model = $notifiable['model'];
            
            return $model::find($key);
        }

        return null;
    }
}
```

At last, register this service in `AppServiceProvider` of your application

```php
public function register()
{
    $this->app->singleton(TelegramNotifiableProvider::class, fn() => new TelegramUserProvider);
}
```

We are ready to go.

## Getting updates

### Register webhook

If you are properly configure bot in `config/telegram.php` this is enough 
to use `telegram:webhook` command provided by `Telegram Bot SDK` 
package. We recommend to read help:

    php artisan help telegram:webhook

This package provides webhook controller to deal with incoming messages. 

For example `DeeplinkCommand`, that was 
mentioned above, used to handle `/start` command with deeplink token.

You are free to add any other command handlers to `config/telegram.php`.

Read more about 
[Bot Commands](https://telegram-bot-sdk.com/docs/guides/commands-system).

### Long polling

This package brings `telegram:poll` command to get updates without 
registering webhook. Just call a command.

## Usage

### Subscribe user

First, we need to issue a deeplink for a user.

```php
use \Illuminate\Http\Request;
use \Codewiser\Telegram\TelegramService;

class DeeplinkController extends Controller
{
    public function __invoke(Request $request, TelegramService $service) {
        return $service->getDeeplink($request->user());
    }
}
```

User follows deeplink, opens telegram client and presses Start button.

`Codewiser\Telegram\Commands\DeeplinkCommand` handles incoming update, 
resolves deeplink token and update `User` with `chat_id`.

For now, this user has telegram route and may be notified via Telegram.

### Unsubscribe user

If user blocks or delete a bot, app can not deliver notification: telegram 
responds with 403 status code. In that case we should count user as 
unsubscribed and may delete `chat_id`.

To do so, just register event listener:

```php
use Codewiser\Telegram\Listeners\UnsubscribeTelegramNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;

Event::listen(NotificationFailed::class, UnsubscribeTelegramNotifiable::class);
```