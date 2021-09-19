<?php

use App\Conversations\MainMenu\StartConversation;
use App\Http\Controllers\BotManController;
use BotMan\BotMan\BotMan;

$botman = resolve('botman');


$botman->hears('/restart', function (BotMan $botMan) {
    $botMan->startConversation(new StartConversation());
});

$botman->hears('/setabouttext', function (BotMan $botMan) {
    $botMan->reply(trans('messages.about myself'));
});


$botman->hears('{any}', BotManController::class . '@startConversation');





