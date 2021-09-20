<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use App\Traits\RegisterTrait;
use App\Traits\SetupCityTrait;
use BotMan\BotMan\Messages\Incoming\Answer;


/**
 * Диалог регистрации пользователя
 */
class RegisterConversation extends BaseConversation
{
    use RegisterTrait;
    use SetupCityTrait;

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
     * TODO: заменить аргументы на один, само сообщение. Если без сообщения то отправляем ''
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
                $this->setupCity();
            } else {
                $this->say(Translator::trans('messages.wrong sms code'));
                $this->confirmSms(false, true);
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
            $this->getCitiesArray(),
            ['config' => ButtonsFormatterService::CITY_MENU_FORMAT]
        );

        return $this->ask($question, function (Answer $answer) {
            if ($this->isUserInputIsCity($answer->getText())) {
                $this->getUser()->updateCity($answer->getText());
                $this->say(Translator::trans('messages.city has been changed', ['city' => $answer->getText()]));
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->setupCity();
            }
        });
    }
}