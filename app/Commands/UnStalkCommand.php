<?php

namespace App\Commands;

use App\Repositories\ChatRepository;
use App\Repositories\StalkRepository;
use Hunter\Hunter;
use Telegram\Bot\Commands\Command;

class UnStalkCommand extends Command
{
    protected $name = "unstalk";
    protected $description = "Stop stalking a UVa user";

    public function handle($arguments)
    {
        $username = explode(' ', trim($arguments))[0];

        if (empty($username)) {
            $this->replyWithMessage(['text' => "Please provide the UVa username of the user you want to stop stalking. Type in\n /unstalk <username>"]);
            return;
        }

        $hunter = new Hunter;
        $uvaID = $hunter->getIdFromUsername($username);
        if ($uvaID === null) {
            $this->replyWithMessage(['text' => "The UVa username $username does not exist"]);
            return;
        }

        $chat = ChatRepository::getChatByID($this->update->getMessage()->getChat()->getId());
        $b = StalkRepository::stopStalk($chat, $uvaID);

        if ($b) {
            $this->replyWithMessage(
                ['text' => "Stopped stalking!\nYou will no longer receive notifications of any UVa submissions from $username."]
            );
        } else {
            $this->replyWithMessage(['text' => "You are not stalking $username\n"]);
        }
    }
}