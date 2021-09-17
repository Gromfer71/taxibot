<?php

namespace App\Conversations;

use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\RegisterTrait;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Диалог регистрации пользователя
 */
class RegisterConversation extends BaseConversation
{
    use RegisterTrait;

    /**
     * Начало
     *
     * @return void
     */
    public function run()
    {
        $this->confirmPhone();
    }

    /**
     * Подтверждение телефона пользователя
     *
     * @return \App\Conversations\RegisterConversation
     */
    public function confirmPhone(): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.enter phone first'));

        return $this->ask($question, function (Answer $answer) {
            if ($this->isPhoneCorrect($answer->getText())) {
                $this->sendSmsCode($answer->getText());
                $this->saveUserPhone($answer->getText());
                $this->confirmSms();
            } else {
                $this->confirmPhone();
            }
        });
    }

    /**
     * Подтверждение и ввод смс кода
     */
    public function confirmSms()
    {

    }


}