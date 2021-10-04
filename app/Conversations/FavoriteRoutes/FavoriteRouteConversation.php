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
            $question = ComplexQuestion::setButtons($question, ['buttons.' . ButtonsStructure::DELETE_ROUTE]);
        }
        ComplexQuestion::addFavoriteRoutesButtons($question, $this->getUser()->favoriteRoutes);


        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());

            $this->createOrder($answer->getText());
        });
    }

    public function addRoute()
    {
        $message = $this->addOrdersRoutesToMessage(Translator::trans('messages.add route menu'));
        $question = ComplexQuestion::createWithSimpleButtons(
            $message,
            [ButtonsStructure::BACK, ButtonsStructure::CREATE_ROUTE],
            ['location' => 'addresses']
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
            if (!$answer->isInteractiveMessageReply()) {
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
            } else {
                $this->setRouteName($address);
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
            if ($answer->getText() != 'back') {
                $this->confirmDeleteRoute($answer->getText());
            } else {
                $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'run']);
            }
        });
    }

    public function confirmDeleteRoute($routeName)
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.confirm delete favorite route'),
            [ButtonsStructure::BACK, ButtonsStructure::DELETE]
        );

        return $this->ask($question, function (Answer $answer) use ($routeName) {
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'deleteRoute']);
            $route = FavoriteRoute::where('name', $routeName)->where('user_id', $this->getUser()->id)->first();
            if ($route) {
                $route->delete();
            }
            $this->deleteRoute();
        });
    }
}