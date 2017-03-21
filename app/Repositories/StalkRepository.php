<?php

namespace App\Repositories;


use App\Chat;
use App\Stalk;
use Carbon\Carbon;

class StalkRepository
{
    public static function startStalk(Chat $chat, $uvaID)
    {
        $stalk = StalkRepository::getStalk($chat, $uvaID);

        if ($stalk) { // Chat was already stalking this UVa user.
            return $stalk;
        }

        return $chat->stalks()->save(new Stalk([
            'uvaID' => $uvaID
        ]));
    }

    /**
     * Stop stalking a UVa user
     *
     * Return false if the chat was not previously stalking the UVa user.
     *
     * @param Chat $chat
     * @param $uvaID
     * @return bool
     */
    public static function stopStalk(Chat $chat, $uvaID): bool
    {
        $stalk = StalkRepository::getStalk($chat, $uvaID);

        if ($stalk) {
            $stalk->deletedAt = Carbon::now();
            $stalk->save();
            return true;
        } else {
            return false;
        }
    }

    private static function getStalk(Chat $chat, $uvaID)
    {
        return Stalk::notDeleted()
            ->where('chat', $chat->chatID)
            ->where('uvaID', $uvaID)
            ->first();
    }
}