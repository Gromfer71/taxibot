<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class FavoriteRouteConversation extends BaseConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::CREATE_ROUTE => 'App\Conversations\MainMenu\TakingAddressForFavoriteRouteConversation',
            ButtonsStructure::ADD_ROUTE => 'addRoute'
        ];
        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.favorite routes menu'),
            [ButtonsStructure::BACK, ButtonsStructure::ADD_ROUTE]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }

    public function addRoute()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.add route menu'),
            [ButtonsStructure::BACK, ButtonsStructure::CREATE_ROUTE]
        );

        $question = ComplexQuestion::addOrderHistoryButtons($question, $this->getUser()->orders);
        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());

            $this->setRouteName($answer->getValue());
        });
    }

    public function setRouteName($addressInfo)
    {
        $this->say($addressInfo);
    }
}