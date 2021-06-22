<?php
use App\Http\Controllers\BotManController;
use App\Models\User;
use App\Services\OrderApiService;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\TelegramDriver;
$botman = resolve('botman');



$botman->hears('/restart', function (BotMan $botMan) {
   $botMan->startConversation(new \App\Conversations\StartConversation());
});

$botman->hears('/setabouttext', function (BotMan $botMan) {
    $botMan->reply(trans('messages.about myself'));
});



$botman->hears('{any}', BotManController::class.'@startConversation');





