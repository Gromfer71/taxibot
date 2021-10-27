<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseConversation;
use App\Conversations\TaxiMenuConversation;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Options;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class FavoriteRouteConversation extends BaseConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
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
            [ButtonsStructure::BACK]
        );

        ComplexQuestion::addFavoriteRoutesButtons($question, $this->getUser()->favoriteRoutes);


        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            if ($this->getUser()->favoriteRoutes->where('name', $answer->getText())->isNotEmpty()) {
                $this->createOrder($answer->getText());
            } else {
                $this->run();
            }
        });
    }

    public function createOrder($routeName)
    {
        $route = $this->getUser()->favoriteRoutes->where('name', $routeName)->first();
        $addressInfo = collect(json_decode($route->address));
        $this->bot->userStorage()->delete();
        $this->bot->userStorage()->save($addressInfo->toArray());
        $this->bot->userStorage()->save(
            [
                'crew_group_id' => (new Options())->getCrewGroupIdFromCity($this->getUser()->city),
                'is_route_from_favorite' => true,
            ]
        );

        $this->bot->startConversation(new TaxiMenuConversation());
    }
}