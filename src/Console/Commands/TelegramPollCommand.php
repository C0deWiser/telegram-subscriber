<?php

namespace Codewiser\Telegram\Console\Commands;

use Codewiser\Telegram\TelegramService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {bot? : Bot name defined in config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Long polling for telegram.';

    public function handle(TelegramService $service): void
    {
        while (true) {

            $message = $service
                ->bot($this->argument('bot'))
                ->commandsHandler(false);

            if ($message) {
                dump($message);
            }

            sleep(10);
        }
    }
}
