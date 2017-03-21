<?php

namespace App\Commands;

use App\Repositories\ChatRepository;
use App\Repositories\StalkRepository;
use Hunter\Hunter;
use Telegram\Bot\Commands\Command;

class StalkCommand extends Command
{
    protected $name = "stalk";
    protected $description = "Stalk a UVa user";

    public function handle($arguments)
    {
        $username = explode(' ', trim($arguments))[0];

        if (empty($username)) {
            $this->replyWithMessage(['text' => "Please provide the UVa username of the user you want to stalk. Type in\n /stalk <username>"]);
            return;
        }

        $hunter = new Hunter;
        $uvaID = $hunter->getIdFromUsername($username);
        if ($uvaID === null) {
            $this->replyWithMessage(['text' => "The UVa username $username does not exist"]);
            return;
        }

        $chat = ChatRepository::getChatByID($this->update->getMessage()->getChat()->getId());
        StalkRepository::startStalk($chat, $uvaID);

        $this->replyWithMessage(
            ['text' => "All set up!\nFrom now on you will receive notifications of any UVa submissions from $username."]
        );

        $this->replyWithMessage(['text' => "You can always stop stalking by typing in\n/unstalk $username\n"]);
    }
}