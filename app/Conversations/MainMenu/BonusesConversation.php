<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Меню бонусов (вход из главного меню)
 */
class BonusesConversation extends BaseConversation
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
            ButtonsStructure::BONUS_BALANCE => function () {
                $this->run(
                    Translator::trans(
                        'messages.get bonus balance',
                        ['bonuses' => $this->getUser()->getBonusBalance() ?? 0]
                    )
                );
            },
            ButtonsStructure::WORK_AS_DRIVER => function () {
                $this->run(Translator::trans('messages.work as driver'));
            },
            ButtonsStructure::OUR_SITE => function () {
                $this->run(Translator::trans('messages.our site'));
            },
            ButtonsStructure::OUR_APP => function () {
                $this->run(Translator::trans('messages.our app'));
            },
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @param string $message
     * @return \App\Conversations\MainMenu\BonusesConversation
     */
    public function run(string $message = ''): BonusesConversation
    {
        if (!$message) {
            $message = Translator::trans('messages.bonuses menu');
        }

        $question = ComplexQuestion::createWithSimpleButtons($message,
                                                             ButtonsStructure::getBonusesMenu(),
                                                             ['config' => ButtonsFormatterService::BONUS_MENU_FORMAT]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                $this->handleAction($answer);
                $this->run();
            }
        );
    }
}