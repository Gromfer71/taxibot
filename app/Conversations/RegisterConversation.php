<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ButtonsStructure;
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
            if($answer->getValue() === ButtonsStructure::RESTART) {
                $this->bot->startConversation(new StartConversation());
                return;
            }

            $this->tryToSendSmsCode($answer->getText());
        });
    }

    /**
     * Подтверждение и ввод смс кода
     */
    public function confirmSms($message = ''): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.enter sms code', ['phone' => $this->getFromStorage('phone')]),
            ['call', 'restart']
        );

        return $this->ask($question, function (Answer $answer) {
            if($answer->getValue() === 'restart') {
                $this->bot->startConversation(new StartConversation());
            } elseif ($answer->getValue() == 'call') {
                $this->callSmsCode();
                $this->saveToStorage(['is_call' => 1]);
                $this->confirmSms(Translator::trans('messages.enter call code'));
            } elseif ($this->isSmsCodeCorrect($answer->getText())) {
                $this->registerUser();
                $this->setupCity();
            } else {
                $this->confirmSms($this->getFromStorage('is_call') ? Translator::trans('messages.incorrect phone code') : Translator::trans('messages.wrong sms code'));
            }
        });
    }

    /**
     * Установка города пользователя
     */
    public function setupCity(): RegisterConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.choose city without current city')
        );

        $question = ComplexQuestion::setButtons(
            $question,
            $this->options->getCitiesArray(),
            ['config' => ButtonsFormatterService::CITY_MENU_FORMAT],
            true
        );

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