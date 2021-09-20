<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
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
     * @param string|null $message
     * @return \App\Conversations\RegisterConversation
     */
    public function confirmPhone(?string $message = ''): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.enter phone first')
        );

        return $this->ask($question, function (Answer $answer) {
            $this->tryToSendSmsCode($answer->getText());
        });
    }

    /**
     * Подтверждение и ввод смс кода
     */
    public function confirmSms($message = ''): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.enter sms code'),
            ['call']
        );

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'call') {
                $this->callSmsCode();
                $this->confirmSms(Translator::trans('messages.enter call code'));
            } elseif ($this->isSmsCodeCorrect($answer->getText())) {
                $this->registerUser();
                $this->setupCity();
            } else {
                $this->confirmSms(Translator::trans('messages.wrong sms code'));
            }
        });
    }

    /**
     * Установка города пользователя
     */
    public function setupCity(): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.choose city without current city'),
            [],
            ['config' => ButtonsFormatterService::CITY_MENU_FORMAT]
        );
        $question = ComplexQuestion::setButtons($question, $this->options->getCitiesArray());

        return $this->ask($question, function (Answer $answer) {
            if ($this->options->isUserInputIsCity($answer->getText())) {
                $this->getUser()->updateCity($answer->getText());
                $this->say(Translator::trans('messages.city has been changed', ['city' => $answer->getText()]));
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->setupCity();
            }
        });
    }
}