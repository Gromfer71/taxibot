<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Models\LangPackage;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\Log;

/**
 *  Меню переключения языка пользователя
 */
class SwitchLangConversation extends BaseConversation
{

    /**
     * Массив действий под определенную кнопку. Если значение это анонимная функция, то выполнится она, если имя метода,
     * то выполнится он в контексте текущего класса, если название класса (с полным путем), то запустится его Conversation.
     *
     * @param array $replaceActions
     * @return array
     */
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @param string $message
     * @return \App\Conversations\MainMenu\SwitchLangConversation
     */
    public function run(string $message = ''): SwitchLangConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.choose lang'),
            [ButtonsStructure::BACK]
        );
        Log::info(json_encode(LangPackage::getPackagesName()));
        $question = ComplexQuestion::setButtons(
            $question,
            LangPackage::getPackagesName(),
            [],
            true
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            if ($langPackage = LangPackage::getByName($answer->getValue())) {
                $this->getUser()->switchLang($langPackage->id);
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->run(Translator::trans('messages.lang not found'));
            }
        });
    }
}