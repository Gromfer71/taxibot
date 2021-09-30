<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseConversation;
use App\Conversations\TaxiMenuConversation;
use App\Models\FavoriteRoute;
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
            ButtonsStructure::CREATE_ROUTE => 'App\Conversations\FavoriteRoutes\TakingAddressForFavoriteRouteConversation',
            ButtonsStructure::ADD_ROUTE => 'addRoute',
            ButtonsStructure::DELETE_ROUTE => 'deleteRoute'
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

        if ($this->getUser()->favoriteRoutes->isNotEmpty()) {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::DELETE_ROUTE]);
        }
        ComplexQuestion::addFavoriteRoutesButtons($question, $this->getUser()->favoriteRoutes);


        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());

            $this->createOrder($answer->getText());
        });
    }

    public function addRoute()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.add route menu'),
            [ButtonsStructure::BACK, ButtonsStructure::CREATE_ROUTE]
        );

        $question = ComplexQuestion::addOrderHistoryButtons($question, $this->getUser()->orders);
        return $this->ask($question, function (Answer $answer) {
            $this->handleAction(
                $answer->getValue(),
                [ButtonsStructure::BACK => 'run']
            );
            $this->setRouteName($answer->getText());
        });
    }

    public function setRouteName($address)
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.write favorite route name'));

        return $this->ask($question, function (Answer $answer) use ($address) {
            FavoriteRoute::create([
                                      'user_id' => $this->getUser()->id,
                                      'name' => $answer->getText(),
                                      'address' => json_encode(
                                          $this->getUser()->getOrderInfoByImplodedAddress(
                                              $address
                                          ),
                                          JSON_UNESCAPED_UNICODE
                                      )
                                  ]);

            $this->run();
        });
    }

    public function createOrder($routeName)
    {
        $route = $this->getUser()->favoriteRoutes->where('name', $routeName)->first();
        $addressInfo = collect(json_decode($route->address));
        $this->bot->userStorage()->delete();
        $this->bot->userStorage()->save($addressInfo->toArray());
        $this->bot->userStorage()->save(
            ['crew_group_id' => (new Options())->getCrewGroupIdFromCity($this->getUser()->city)]
        );

        $this->bot->startConversation(new TaxiMenuConversation());
    }

    public function deleteRoute()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.delete route menu'),
            [ButtonsStructure::BACK]
        );
        $question = ComplexQuestion::addFavoriteRoutesButtons($question, $this->getUser()->favoriteRoutes);

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'run']);

            $this->confirmDeleteRoute();
        });
    }

    public function confirmDeleteRoute()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.confirm delete favorite route'),
            [ButtonsStructure::DELETE, ButtonsStructure::BACK]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'deleteRoute']);
            $route = $this->getUser()->favoriteRoutes->where('name', $answer->getText())->first();
            if ($route) {
                $route->delete();
            }
            $this->deleteRoute();
        });
    }
}