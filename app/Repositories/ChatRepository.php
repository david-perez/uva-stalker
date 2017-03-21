<?php

namespace App\Repositories;

use App\Chat;

class ChatRepository
{
    public static function getChatByID($chatID)
    {
        return Chat::firstOrCreate(['chatID' => $chatID]);
    }
}