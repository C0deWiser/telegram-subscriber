# Laravel Telegram Channel

This package provides a way to send notifications via Telegram.

## Installation and setup

Package uses `irazasyed/telegram-bot-sdk`. So above all follow 
[Telegram Bot SDK 
instructions](https://telegram-bot-sdk.com/docs/getting-started/installation)
and set up your first Telegram Bot.

In `config/telegram.php` configuration file add bot `name` parameter and 
register `DeeplinkCommand`:

```php
'bots' => [
    'my_bot' => [
        'name'             => env('TELEGRAM_BOT_NAME'),
        'token'            => env('TELEGRAM_BOT_TOKEN'),
        'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH'),
        'webhook_url'      => env('TELEGRAM_WEBHOOK_URL'),
        'commands'         => [
            \Codewiser\Telegram\Commands\DeeplinkCommand::class
        ],
    ],
]
```

Next, implement `\Codewiser\Telegram\Contracts\TelegramNotifiable` to a 
`User` model. You might need to write a migration...

```php
use \Illuminate\Database\Eloquent\Model;
use \Codewiser\Telegram\Contracts\TelegramNotifiable;

class User extends Model implements TelegramNotifiable
{
    public function routeNotificationForTelegram($notification = null): mixed
    {
        return $this->telegram;
    }

    public function setRouteForTelegram($route): void
    {
        $this->telegram = $route;
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
            $notifiable->getKey(),
            now()->addMinutes(5)
        );

        return $token;
    }
    
    /**
     * Find notifiable associated with a given token.
     */
    public function resolveToken(string $token): ?TelegramNotifiable
    {
        $key = cache()->pull($token);

        if ($key) {
            return User::query()->find($key);
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
to use `telegram:webhook` command, that is provided by `Telegram Bot SDK` 
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

### Notify user

To notify user via Telegram, add `toTelegram` method to a notification. Do 
not forget to add `telegram` to `via` method.

```php
class Notification extends \Illuminate\Notifications\Notification
{
    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'telegram'];
    }
    
    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable)
    {
        //
    }
}
```

Telegram notification message may be as string, as array.

Array keys fits [Telegram sendMessage 
method](https://core.telegram.org/bots/api#sendmessage).

String interprets as html and will be sent with `['parse_mode' => 'HTML']`.

### Failed notifications

When notification is failed to deliver to a user, 
`\Codewiser\Telegram\Events\NotificationDeliveryFailed` event is propagated.

Some fails are catchable. For example, if user locks a bot, we will get a 
`403` response status. In that case we should unsubscribe user from future 
notifications.

```php
use \Codewiser\Telegram\Events\NotificationDeliveryFailed;
use \Codewiser\Telegram\Contracts\TelegramNotifiable;

class DeliveryFailedListener 
{
    public function handle(NotificationDeliveryFailed $event): void
    {
        if ($event->channel == 'telegram') {
            if ($event->exception instanceof GuzzleException &&
                $event->exception->getCode() == 403]) {
                if ($event->notifiable instanceof TelegramNotifiable) {
                    $event->notifiable->setRouteForTelegram(null);
                }
            }
        }
    }
}
```