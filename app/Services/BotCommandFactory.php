<?php

namespace App\Services;

use App\Conversations\BaseConversation;
use App\Conversations\RegisterConversation;
use App\Services\BotCommands\AboutBotCommand;
use App\Services\BotCommands\Navigation\GoToStartMenuCommand;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Фабрика для создания команд бота
 */
class BotCommandFactory
{
    /**
     * Фабрика возвращает экземпляр нужной команды в зависимости от ответа пользователя
     *
     * @param \BotMan\BotMan\Messages\Incoming\Answer $answer
     * @param \App\Conversations\BaseConversation $conversation
     * @return \App\Services\BotCommands\AboutBotCommand|void
     */
    public static function factory(Answer $answer, BaseConversation $conversation)
    {
        switch ($answer->getValue()) {
            case 'start menu':
            {
                 $conversation->getBot()->startConversation(new RegisterConversation());
            }
            break;
            case '':
            {
                return new AboutBotCommand($conversation);
            }
            break;
        }

        switch ($answer->getText()) {
            case '/setabouttext':
            {
                return new AboutBotCommand($conversation);
            }
            break;
        }
    }
}