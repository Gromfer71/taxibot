<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('{any}', BotManController::class . '@startConversation');





