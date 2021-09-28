<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;

class FavoriteRouteConversation extends BaseConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::ADD_ROUTE => 'App\Conversations\MainMenu\TakingAddressForFavoriteRouteConversation'
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
}