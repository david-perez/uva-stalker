<?php

namespace App\Commands;

use Telegram\Bot\Commands\Command;

class HelpCommand extends Command
{
    protected $name = 'help';
    protected $description = 'Get a list of commands';

    public function handle($arguments)
    {
        $commands = $this->telegram->getCommands();

        $text = '';
        foreach ($commands as $name => $handler) {
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(compact('text'));
    }
}
