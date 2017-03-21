<?php

namespace App\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected $name = "start";
    protected $description = "Start Command to get you started";

    public function handle($arguments)
    {
        $this->replyWithMessage(['text' => 'Hello! Welcome to UVaStalker, here are the available commands:']);

        // This will update the chat status to typing...
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        // This will prepare a list of available commands and send the user.
        $commands = $this->getTelegram()->getCommands();

        // Build the list.
        $response = '';
        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        // Reply with the commands list
        $this->replyWithMessage(['text' => $response]);
    }
}