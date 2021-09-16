<?php

namespace App\Services\BotCommands\Navigation;

use App\Conversations\MenuConversation;
use App\Services\BotCommands\BaseBotCommand;
use App\Services\BotCommands\BotCommandInterface;

class GoToStartMenuCommand extends BaseBotCommand implements BotCommandInterface
{
    public function execute()
    {
        parent::execute();

        $this->bot->startConversation(new MenuConversation());
    }
}