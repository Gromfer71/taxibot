<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseConversation;
use App\Models\FavoriteRoute;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class FavoriteRouteSettingsConversation extends BaseConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::CREATE_ROUTE => 'App\Conversations\FavoriteRoutes\TakingAddressForFavoriteRouteConversation',
            ButtonsStructure::ADD_ROUTE => 'addRoute',
            ButtonsStructure::DELETE_ROUTE => 'deleteRoute',
            ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY => function () {
                $this->getUser()->favoriteRoutes->each(function ($item) {
                    $item->delete();
                });
                $this->say(Translator::trans('messages.clean addresses history'));
                $this->run();
            }
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

            $this->confirmDeleteRoute($answer->getText());
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
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.write favorite route name'),
            [ButtonsStructure::BACK]
        );

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
                $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'addRoute']);
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
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'run']);
            $route = FavoriteRoute::where('name', $routeName)->where('user_id', $this->getUser()->id)->first();
            if ($route) {
                $route->delete();
            }
            $this->run();
        });
    }
}