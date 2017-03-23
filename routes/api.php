<?php

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post(getenv('TELEGRAM_BOT_TOKEN'), function (Request $request) {
    $update = Telegram::commandsHandler(true);
    return $update;
});