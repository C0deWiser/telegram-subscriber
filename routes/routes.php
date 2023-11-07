<?php

use Codewiser\Telegram\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('telegram/{bot}/{token}', function (Request $request, TelegramService $service) {

    $service
        ->bot($request->route('bot'), $request->route('token'))
        ->commandsHandler(true);

    return 'ok';
});
