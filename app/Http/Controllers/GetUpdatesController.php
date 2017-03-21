<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Telegram\Bot\Laravel\Facades\Telegram;

class GetUpdatesController extends Controller
{
    public function receive()
    {
        $update = Telegram::commandsHandler(true);
        return $update;
    }
}
