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
    public function confirmSms(): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.enter sms code'),
            ['buttons.call']
        );

        return $this->ask($question, function (Answer $answer) {
            if($answer->getValue() == 'call') {
                $this->callSmsCode();
                $this->confirmSms();
            } elseif($this->isSmsCodeCorrect($answer->getText())) {
                $this->registerUser();

                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->say(Translator::trans('messages.wrong sms code'));
                $this->confirmSms();
            }
        });
    }


}