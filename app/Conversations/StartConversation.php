<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\BotManagerTrait;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\BotMan\Messages\Incoming\Answer;

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
            $this->chooseLang();
        }
    }

    public function chooseLang()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.choose lang', ['lang' => LangPackage::getByCode($this->getFromStorage('lang'))->name ?? (LangPackage::find(LangPackage::getDefaultLangId())->name ?? '')])
        );

        $question = ComplexQuestion::setButtons(
            $question,
            LangPackage::getOnlyEnablesPackagesName(),
            [],
            true
        );

        return $this->ask($question, function (Answer $answer) {
            if ($langPackage = LangPackage::getByName($answer->getText())) {
                $this->saveToStorage(['lang' => $langPackage->code]);
                Translator::$lang = $langPackage->code;
                $this->register();
            } else {
                $this->chooseLang();
            }
        });
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