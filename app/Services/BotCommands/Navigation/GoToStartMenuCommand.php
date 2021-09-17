<?php

namespace App\Services\BotCommands\Navigation;

use App\Conversations\MenuConversation;
use App\Services\BotCommands\BaseBotCommand;
use App\Services\BotCommands\BotCommandInterface;

/**
 * Переводит пользователя в главное меню
 */
class GoToStartMenuCommand extends BaseBotCommand implements BotCommandInterface
{
    /**
     * Выполнение команды
     *
     * @return void
     */
    public function execute()
    {
        parent::execute();

        $this->bot->startConversation(new MenuConversation());
    }
}