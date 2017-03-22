<?php

namespace App\Commands;

use App\Repositories\ChatRepository;
use Hunter\Hunter;
use Telegram\Bot\Commands\Command;

class StalkingCommand extends Command
{
    protected $name = "stalking";
    protected $description = "List currently stalking users";

    public function handle($arguments)
    {
        $chat = ChatRepository::getChatByID($this->update->getMessage()->getChat()->getId());
        $stalks = $chat->stalks;

        $hunter = new Hunter;

        $text = '';
        foreach ($stalks as $stalk) {
            $user = $stalk->UVaUser;
            $row = ($hunter->userRanklist($user->uvaID, 0, 0))[0];
            $text .= sprintf('_%s_ ([%s](http://uhunt.felix-halim.net/id/%d)) - %d ACs, ranked %d'.PHP_EOL, $row['name'],
                $user->username,
                $user->uvaID,
                $row['accepted'],
                $row['rank']);
        }


        $this->replyWithMessage(
            ['text' => "You are stalking {$stalks->count()} user" . ($stalks->count() === 1 ? '' : 's'), 'parse_mode' => 'Markdown']
        );

        if ($stalks->count() > 0) {
            $this->replyWithMessage(['text' => $text, 'parse_mode' => 'Markdown']);
        }
    }
}