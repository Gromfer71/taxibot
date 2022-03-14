<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\BotManagerTrait;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;

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
        $question = Question::create(OutgoingMessage::create('test', new File('https://static-cse.canva.com/blob/195615/paul-skorupskas-7KLa-xLbSXA-unsplash-2.jpg')))
            ->addButton(Button::create(trans('start menu'))->value('start menu'));


        return $this->ask($question, function () {
            $this->bot->startConversation(new RegisterConversation());
        });
    }
}