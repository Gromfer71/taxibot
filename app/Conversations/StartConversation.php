<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\BotManagerTrait;

/**
 * Первый класс диалога. Запускается в первую очередь для нового пользователя
 */
class StartConversation extends BaseConversation
{
    use BotManagerTrait;

    /**
     * Начало
     *
     * @return void
     */
    public function run(): void
    {
        $this->checkProgramForBroken();

        if ($this->isUserRegistered()) {
            $this->bot->startConversation(new MenuConversation());
        } else {
            $this->register();
        }
    }

    /**
     * Регистрация пользователя в системе
     *
     * @return \App\Conversations\StartConversation
     */
    public function register(): StartConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.welcome message'),
            ['start menu']
        );

        return $this->ask($question, function () {
            $this->bot->startConversation(new RegisterConversation());
        },                ['welcome_message' => true]);
    }
}