<?php

namespace App\Services\BotCommands;

use App\Services\Translator;

class AboutBotCommand extends BaseBotCommand implements BotCommandInterface
{
    public function execute()
    {
        parent::execute();

        $this->conversation->say(Translator::trans('messages.about myself'));
    }
}