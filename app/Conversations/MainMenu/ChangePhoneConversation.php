<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\RegisterTrait;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Смена номера телефона пользователя в главном меню
 */
class ChangePhoneConversation extends BaseConversation
{
    use RegisterTrait;

    /**
     * @return void
     */
    public function run()
    {
        $this->confirmPhone(Translator::trans('messages.enter phone'));
    }

    /**
     * @param string $message
     * @return \App\Conversations\MainMenu\ChangePhoneConversation
     */
    public function confirmPhone(string $message = ''): ChangePhoneConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons($message);

        return $this->ask($question, function (Answer $answer) {
            $this->tryToSendSmsCode($answer->getText());
        });
    }

    /**
     * @param string $message
     * @return \App\Conversations\MainMenu\ChangePhoneConversation
     */
    public function confirmSms(string $message = ''): ChangePhoneConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.enter sms code'), ['call']
        );

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'call') {
                $this->callSmsCode();
                $this->confirmSms(Translator::trans('messages.enter call code'));
            } elseif ($this->isSmsCodeCorrect($answer->getText())) {
                $this->getUser()->updatePhone($this->getFromStorage('phone'));
                $this->say(Translator::trans('messages.phone changed', ['phone' => $this->getFromStorage('phone')]));
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->confirmSms(Translator::trans('messages.wrong sms code'));
            }
        });
    }
}