<?php

namespace App\Conversations;

use App\Models\Log;
use App\Services\Bot\ComplexQuestion;
use App\Services\BotCommandFactory;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

/**
 * Первый класс диалога. Запускается в первую очередь для нового пользователя
 */
class StartConversation extends BaseConversation
{
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
            $this->registerUser();
        }
    }


    public function register()
    {
        $this->registerUser();
        $question = ComplexQuestion::createWithSimpleButtons($this->__('messages.welcome message'), ['start menu']);
        return $this->ask($question, $this->getDefaultCallback());
    }

    public function aboutMyself()
    {
        $question = Question::create($this->__('messages.about myself'))
            ->addButton(Button::create($this->__('buttons.menu'))->value('menu'));

        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->getValue() == 'menu') {
                    $this->bot->startConversation(new MenuConversation());
                }
            }
        );
    }

    private function checkProgramForBroken()
    {
        if ($this->bot->userStorage()->get('error')) {
            $this->say($this->__('messages.program error message'));
            $this->bot->userStorage()->delete('error');
        }
    }
}