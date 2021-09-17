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
     * @param null $noMessage
     * @return \App\Conversations\RegisterConversation
     */
    public function confirmPhone($noMessage = null): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $noMessage ? '' : Translator::trans('messages.enter phone first')
        );

        return $this->ask($question, function (Answer $answer) {
            if ($this->isPhoneCorrect($answer->getText())) {
                $this->sendSmsCode($answer->getText());
                $this->saveUserPhone($answer->getText());
                $this->confirmSms();
            } else {
                $this->say(Translator::trans('messages.incorrect phone format'));
                $this->confirmPhone(true);
            }
        });
    }

    /**
     * Подтверждение и ввод смс кода
     */
    public function confirmSms($willCall = false, $noMessage = false): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $willCall ? Translator::trans('messages.enter call code') : ($noMessage ? '' : Translator::trans(
                'messages.enter sms code'
            )),
            ['buttons.call']
        );

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'call') {
                $this->callSmsCode();
                $this->confirmSms(true);
            } elseif ($this->isSmsCodeCorrect($answer->getText())) {
                $this->registerUser();
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->say(Translator::trans('messages.wrong sms code'));
                $this->confirmSms(false, true);
            }
        });
    }

    /**
     * Установка города пользователя
     */
    public function setupCity()
    {

    }


}