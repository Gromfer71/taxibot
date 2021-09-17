<?php

namespace App\Services\BotCommands;

use App\Services\Translator;

/**
 * Показывает пользователю информацию о боте. Без перехода куда-либо
 */
class AboutBotCommand extends BaseBotCommand implements BotCommandInterface
{
    /**
     * Выполнение команды
     *
     * @return void
     */
    public function execute()
    {
        parent::execute();

        $this->conversation->say(Translator::trans('messages.about myself'));
    }
}