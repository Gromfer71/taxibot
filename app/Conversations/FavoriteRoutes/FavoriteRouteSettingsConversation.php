<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseConversation;
use App\Conversations\Settings\SettingsConversation;
use App\Models\FavoriteRoute;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class FavoriteRouteSettingsConversation extends BaseConversation
{
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => SettingsConversation::class,
            ButtonsStructure::CREATE_ROUTE => TakingAddressForFavoriteRouteConversation::class,
            ButtonsStructure::ADD_ROUTE => 'addRoute',
            ButtonsStructure::CANCEL => '',
        ];
        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run()
    {
        if ($this->getFromStorage('go_to_add_route_menu')) {
            $this->saveToStorage(['go_to_add_route_menu' => false]);
            $this->addRoute();
            return;
        }

        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.add favorite routes menu'),
            [ButtonsStructure::BACK, ButtonsStructure::ADD_ROUTE]
        );

        ComplexQuestion::addFavoriteRoutesButtons($question, $this->getUser()->favoriteRoutes);


        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer) ?: $this->confirmDeleteRoute($answer->getText());
        });
    }

    public function addRoute()
    {
        $message = $this->addOrdersRoutesToMessage(Translator::trans('messages.add route menu'));
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $additional = [
                'location' => 'addresses',
                'config' => ButtonsFormatterService::SPLIT_BY_THREE_EXCLUDE_TWO_LINES
            ];
        } else {
            $additional = [];
        }
        $question = ComplexQuestion::createWithSimpleButtons(
            $message,
            [ButtonsStructure::BACK, ButtonsStructure::CREATE_ROUTE],
            $additional
        );


        $question = ComplexQuestion::addOrderHistoryButtons($question, $this->getUser()->orders);
        return $this->ask($question, function (Answer $answer) {
            $this->saveToStorage(['dont_save_address_to_history' => true]);
            if ($this->handleAction($answer, [ButtonsStructure::BACK => 'run'])) {
                return;
            }

            if (!$answer->getValue() && property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
                $address = collect($this->getFromStorage('address_in_number'));
                $address = $address->get($answer->getText());
            } else {
                $address = $answer->getText();
            }

            $addressToSave = $this->getUser()->getOrderInfoByImplodedAddress($address, $this->bot->userStorage());
            if (!$addressToSave) {
                $this->addRoute();
            } else {
                $this->saveMenu($addressToSave);
            }
        });
    }

    public function saveMenu($address)
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.added favorite route menu') . ' ' . implode(
                ' - ',
                $address['address']
            ),
            [ButtonsStructure::SAVE, ButtonsStructure::CANCEL]
        );

        return $this->ask($question, function (Answer $answer) use ($address) {
            if ($answer->getValue() == ButtonsStructure::SAVE) {
                $this->setRouteName($address);
            } elseif ($answer->getValue() == ButtonsStructure::CANCEL) {
                $this->run();
            } else {
                $this->saveMenu($address);
            }
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
                                              $address,
                                              JSON_UNESCAPED_UNICODE
                                          ),
                                          'crew_group_id' => $this->getFromStorage('crew_group_id') ?: null,
                                      ]);

                $this->run();
            } else {
                $this->handleAction($answer, [ButtonsStructure::BACK => 'addRoute']);
            }
        });
    }


    public function confirmDeleteRoute($routeName)
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans(
                'messages.confirm delete favorite route',
                ['route' => implode(' – ', collect(json_decode(FavoriteRoute::where('name', $routeName)->where('user_id', $this->getUser()->id)->first()->address)->address)->toArray())]
            ),
            [ButtonsStructure::BACK, ButtonsStructure::DELETE]
        );

        return $this->ask($question, function (Answer $answer) use ($routeName) {
            if ($this->handleAction($answer, [ButtonsStructure::BACK])) {
                return;
            }
            $route = FavoriteRoute::where('name', $routeName)->where('user_id', $this->getUser()->id)->first();
            if ($route) {
                $route->delete();
            }
            $this->run();
        });
    }
}