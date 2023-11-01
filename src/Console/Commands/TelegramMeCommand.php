<?php

namespace Codewiser\Telegram\Console\Commands;

use Codewiser\Telegram\TelegramService;
use Illuminate\Console\Command;

class TelegramMeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:me {bot? : Bot name defined in config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Telegram bot info.';

    public function handle(TelegramService $service): void
    {
        $me = $service
            ->bot($this->argument('bot'))
            ->getMe()
            ->toArray();

        $this->table(array_keys($me), [$me]);
    }
}
