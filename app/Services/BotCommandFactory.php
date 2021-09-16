<?php

namespace App\Services;

use App\Conversations\BaseConversation;
use App\Services\BotCommands\AboutBotCommand;
use App\Services\BotCommands\Navigation\GoToStartMenuCommand;
use BotMan\BotMan\Messages\Incoming\Answer;

class BotCommandFactory
{
    public static function factory(Answer $answer, BaseConversation $conversation)
    {
        switch ($answer->getValue()) {
            case 'start menu':
            {
                return new GoToStartMenuCommand($conversation);
            }
            case '/setabouttext':
            {
                return new AboutBotCommand($conversation);
            }
        }
    }
}