<?php

namespace App\Conversations;

use App\Conversations\FavoriteRoutes\AddedRouteMenuConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\MessageGeneratorService;
use App\Services\Options;
use App\Services\OrderApiService;
use App\Services\Translator;
use App\Services\WishesService;
use App\Traits\BotManagerTrait;
use BotMan\BotMan\Messages\Incoming\Answer;

class TaxiMenuConversation extends BaseAddressConversation
{
    use BotManagerTrait;

    public function getActions(array $replaceActions = []): array
    {
        $order = OrderHistory::getActualOrder(
            $this->bot->getUser()->getId(),
            $this->bot->getDriver()->getName()
        );
        $actions = [
            ButtonsStructure::EXIT_TO_MENU => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::ADD_ADDRESS => 'App\Conversations\TakingAdditionalAddressConversation',
            ButtonsStructure::GO_FOR_BONUSES => '_go_for_bonuses',
            ButtonsStructure::GO_FOR_CASH => '_go_for_cash',
            ButtonsStructure::WRITE_COMMENT => 'writeComment',
            ButtonsStructure::WISHES => 'wishes',
            ButtonsStructure::CHANGE_PRICE => 'changePrice',
            ButtonsStructure::CANCEL_ORDER => 'cancelOrder',
            ButtonsStructure::BACK => 'run',
            ButtonsStructure::ORDER_INFO => function () {
                $this->say(Translator::trans('messages.pls wait we are searching auto now'));
                $this->currentOrderMenu(true, true);
            },
            ButtonsStructure::NEED_DISPATCHER => function () {
                $this->getUser()->setUserNeedDispatcher();
                $this->say(Translator::trans('messages.wait for dispatcher'));
                $this->currentOrderMenu(true, true);
            },
            ButtonsStructure::ORDER_CONFIRM => function () use ($order) {
                $order->confirmOrder();
                $this->confirmOrder();
            },
            ButtonsStructure::CLIENT_GOES_OUT => function () use ($order) {
                (new OrderApiService())->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                $this->inWay();
            },
            ButtonsStructure::CLIENT_GOES_OUT_LATE => function () use ($order) {
                $this->inWay();
            },
            ButtonsStructure::NEED_DRIVER => function () use ($order) {
                (new OrderApiService())->connectClientAndDriver($order);
                $this->say(Translator::trans('messages.connect with driver'));
                $this->currentOrderMenu(true, true);
            },
            ButtonsStructure::FINISH_ORDER => function () use ($order) {
                if ($order) {
                    $order->finishOrder();
                }
                $this->end();
//                $this->say(Translator::trans('messages.thx for order'));
//                $this->bot->startConversation(new StartConversation());
            },
            ButtonsStructure::ADD_TO_FAVORITE_ROUTES => function () {
                $this->bot->userStorage()->save(['order_already_done' => true]);
                $this->bot->startConversation(new AddedRouteMenuConversation());
            },
            ButtonsStructure::ABORTED_ORDER => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::CANCEL_CHANGE_PRICE => function () use ($order) {
                $order->changed_price = null;
                $order->save();
                $this->bot->userStorage()->save(['changed_price_in_order' => null, 'price' => $order->price]);
                $order->changePrice($this->bot);
                $this->currentOrderMenu();
            },
            ButtonsStructure::CANCEL_LAST_WISH => function () {
                $wishes = collect($this->bot->userStorage()->get('wishes'));
                $wishes->pop();
                $this->bot->userStorage()->save(['wishes' => $wishes]);
                $this->wishes(true);
            },
            ButtonsStructure::NEED_MAP => function () {
                $this->sendDriverMap();
                $this->confirmOrder(true);
            },
            ButtonsStructure::GET_DRIVER_LOCATION => function () {
                $this->sendDriverMap();
                $this->inWay(true);
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run()
    {
        $this->calcPrice();
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage());
        $question = ComplexQuestion::createWithSimpleButtons(
            MessageGeneratorService::getFullOrderInfoFromStorage($this->bot->userStorage()),
            [ButtonsStructure::EXIT_TO_MENU], ['config' => ButtonsFormatterService::TAXI_MENU_FORMAT]
        );
        if ($haveEndAddress) {
            $question = ComplexQuestion::setButtons(
                $question,
                [ButtonsStructure::ADD_ADDRESS]
            );

            $question = ComplexQuestion::setButtons(
                $question,
                [ButtonsStructure::GO_FOR_CASH, ButtonsStructure::WRITE_COMMENT]
            );
        } else {
            $question = ComplexQuestion::setButtons(
                $question,
                [ButtonsStructure::WRITE_COMMENT, ButtonsStructure::GO_FOR_CASH]
            );
        }

        if ($haveEndAddress) {
            $question = ComplexQuestion::setButtons(
                $question,
                [ButtonsStructure::GO_FOR_BONUSES]
            );
        }

        $question = ComplexQuestion::setButtons(
            $question,
            [

                ButtonsStructure::WISHES,
                ButtonsStructure::CHANGE_PRICE
            ]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }

    public function calcPrice()
    {
        $options = new Options();
        $crewGroupId = collect($this->bot->userStorage()->get('crew_group_id'))->first();

        if (!$crewGroupId) {
            $city = User::find($this->bot->getUser()->getId())->city;
            $crewGroupId = $options->getCrewGroupIdFromCity($city ?? null);
        }
        $api = new OrderApiService();
        $tariff = $api->selectTariffForOrder(
            $crewGroupId,
            $this->bot->userStorage()->get('lat'),
            $this->bot->userStorage()->get('lon')
        );
        $priceResponse = $api->calcOrderPrice(
            $tariff->data->tariff_id,
            $options->getOrderParamsArray($this->bot),
            $this->bot->userStorage()
        );
        if (!isset($priceResponse->data->sum)) {
            $this->_sayDebug('Цена поездки посчиталась некорректно!');
        }

        $this->bot->userStorage()->save(['price' => $priceResponse->data->sum ?? 100]);
        $this->bot->userStorage()->save(['tariff_id' => $tariff->data->tariff_id]);
        $this->bot->userStorage()->save(['crew_group_id' => $crewGroupId]);
    }

    public function currentOrderMenu($withMessageAboutOrderCreated = null, $exactlyWithoutMessage = false)
    {
        if ($withMessageAboutOrderCreated) {
            $message = MessageGeneratorService::getFullOrderInfoFromStorage2($this->bot->userStorage());
        } else {
            $message = MessageGeneratorService::getFullOrderInfoFromStorage($this->bot->userStorage());
        }

        if ($exactlyWithoutMessage) {
            $message = '';
        }

        $question = ComplexQuestion::createWithSimpleButtons(
            $message,
            [
                ButtonsStructure::NEED_DISPATCHER,
                ButtonsStructure::ORDER_INFO,
                ButtonsStructure::CANCEL_ORDER,
                ButtonsStructure::CHANGE_PRICE
            ],
            ['config' => ButtonsFormatterService::CURRENT_ORDER_MENU_FORMAT]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer, [ButtonsStructure::CHANGE_PRICE => 'changePriceInOrderMenu']);
            $question = $this->getQuestionInOrderFromCron();
            if ($question) {
                $this->say($question);
                $this->currentOrderMenu(null, true);
            } else {
                $this->currentOrderMenu();
            }
        });
    }

    public function confirmOrder($withoutMessage = false)
    {
        $question = ComplexQuestion::createWithSimpleButtons($withoutMessage ? '' : Translator::trans('messages.auto in way'),
                                                             [
                                                                 ButtonsStructure::NEED_DISPATCHER,
                                                                 ButtonsStructure::NEED_DRIVER,
                                                                 ButtonsStructure::CANCEL_ORDER,
                                                                 ButtonsStructure::NEED_MAP
                                                             ],
                                                             ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
        );
        $order = OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName());

        return $this->ask($question, function (Answer $answer) use ($order) {
            $this->handleAction($answer, [
                ButtonsStructure::NEED_DRIVER => function () use ($order) {
                    (new OrderApiService())->connectClientAndDriver($order);
                    $this->say(Translator::trans('messages.connect with driver'));
                    $this->confirmOrder(true);
                },
                ButtonsStructure::NEED_DISPATCHER => function () {
                    $this->getUser()->setUserNeedDispatcher();
                    $this->say(Translator::trans('messages.wait for dispatcher'));
                    $this->confirmOrder(true);
                }
            ]);
            if (!OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName())) {
                $this->end();
            } else {
                $question = $this->getQuestionInOrderFromCron();
                if ($question) {
                    $this->say($question);
                    $this->confirmOrder(true);
                } else {
                    $this->confirmOrder();
                }
            }
        });
    }

    public function inWay($withoutMessage = false)
    {
        $question = ComplexQuestion::createWithSimpleButtons($withoutMessage ? '' : Translator::trans('messages.have a nice trip'),
                                                             [
                                                                 ButtonsStructure::FINISH_ORDER,
                                                                 ButtonsStructure::NEED_DISPATCHER,
                                                                 ButtonsStructure::NEED_DRIVER,
                                                             ],
                                                             ['config' => ButtonsFormatterService::SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT]
        );
        if ($this->getActualOrderStateId() == OrderHistory::CLIENT_INSIDE) {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::GET_DRIVER_LOCATION]);
        } else {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::NEED_MAP]);
        }
        $order = OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName());
        return $this->ask($question, function (Answer $answer) use ($order) {
            $this->handleAction(
                $answer,
                [
                    ButtonsStructure::NEED_DRIVER => function () use ($order) {
                        (new OrderApiService())->connectClientAndDriver($order);
                        $this->say(Translator::trans('messages.connect with driver'));
                        $this->inWay(true);
                    },
                    ButtonsStructure::NEED_DISPATCHER => function () {
                        $this->getUser()->setUserNeedDispatcher();
                        $this->say(Translator::trans('messages.wait for dispatcher'));
                        $this->inWay(true);
                    },
                    ButtonsStructure::NEED_MAP => function () {
                        $this->sendDriverMap();
                        $this->inWay(true);
                    }
                ]
            );
            if (!OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName())) {
                $this->end();
            } else {
                $this->inWay();
            }
        });
    }

    public function cancelOrder()
    {
        $order = OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName());
        if ($order) {
            $order->cancelOrder();
        }
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.cancel order'),
            [ButtonsStructure::CONTINUE]
        );

        return $this->ask($question, function () {
            $this->bot->startConversation(new MenuConversation());
        });
    }

    public function changePriceInOrderMenu()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.current price', ['price' => $this->bot->userStorage()->get('price')])
        );

        $prices = (new Options())->getChangePriceOptionsInOrderMenu();
        $prices = $this->_filterChangePrice($prices, 'changed_price_in_order');
        $question = $this->_addChangePriceDefaultButtons($question);
        $question = $this->getChangePrice($question, $prices);

        return $this->ask($question, function (Answer $answer) use ($prices) {
            $this->handleAction($answer, [
                ButtonsStructure::BACK => function () {
                    $this->currentOrderMenu(true);
                }
            ]);
            $price = collect($prices)->filter(function ($item) use ($answer) {
                if ($item->description == $answer->getText()) {
                    return $item;
                } else {
                    return false;
                }
            })->first();
            if (!$price) {
                $this->changePriceInOrderMenu();
                return;
            }
            $order = OrderHistory::getActualOrder(
                $this->bot->getUser()->getId(),
                $this->bot->getDriver()->getName()
            );
            $order->changed_price = (int)$price->id;
            $order->save();
            $this->bot->userStorage()->save(
                ['changed_price_in_order' => $price, 'price' => $order->price + $price->value]
            );
            $order->changePrice($this->bot);
            $this->currentOrderMenu(true);
        });
    }

    public function changePrice()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.current price', ['price' => $this->bot->userStorage()->get('price')])
        );

        $prices = (new Options())->filterChangePriceOptions(User::find($this->bot->getUser()->getId())->city);
        $prices = $this->_filterChangePrice($prices);
        $question = $this->_addChangePriceDefaultButtons($question);
        $question = $this->getChangePrice($question, $prices);

        return $this->ask($question, function (Answer $answer) use ($prices) {
            $this->handleAction($answer, [
                ButtonsStructure::CANCEL_CHANGE_PRICE => function () {
                    $this->bot->userStorage()->save(['changed_price' => null]);
                    $this->run();
                }
            ]);
            $price = collect($prices)->filter(function ($item) use ($answer) {
                if ($item->description == $answer->getText()) {
                    return $item;
                } else {
                    return false;
                }
            })->first();
            if (!$price) {
                $this->changePrice();
                return;
            }
            $this->bot->userStorage()->save(['changed_price' => $price]);
            $this->run();
        });
    }

    public function writeComment()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.write comment or choose'),
            [ButtonsStructure::BACK, ButtonsStructure::GO_FOR_CASH],
            ['config' => ButtonsFormatterService::COMMENT_MENU_FORMAT]
        );

        if (Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage())) {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::GO_FOR_BONUSES]);
        }

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer);
            $this->bot->userStorage()->save(['comment' => $answer->getText()]);
            $this->menuAfterWrittenComment();
        });
    }

    public function menuAfterWrittenComment()
    {
        $data = [
            'route' => MessageGeneratorService::implodeAddress(collect($this->bot->userStorage()->get('address'))),
            'address' => collect($this->bot->userStorage()->get('address'))->first(),
            'price' => $this->bot->userStorage()->get('price'),
            'comment' => $this->bot->userStorage()->get('comment'),
        ];
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage());

        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans(
                $this->bot->userStorage()->get('second_address_will_say_to_driver_flag') ? 'messages.komment_i_pozhelanie_skazhu_voditelu_punkt_6' : 'messages.order info with comment',
                $data
            ),
            [ButtonsStructure::BACK, ButtonsStructure::WISHES, ButtonsStructure::GO_FOR_CASH],
            ['config' => ButtonsFormatterService::SPLITBYTWO_MENU_FORMAT]
        );
        if ($haveEndAddress) {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::GO_FOR_BONUSES]);
        }

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer);
            $this->bot->userStorage()->save(['comment' => $answer->getText()]);
            $this->menuAfterWrittenComment();
        });
    }

    public function wishes($second = false)
    {
        $this->calcPrice();
        $wishes = collect($this->bot->userStorage()->get('wishes'));

        $question = ComplexQuestion::createWithSimpleButtons(
            $second ? MessageGeneratorService::getFullOrderInfoFromStorage(
                $this->bot->userStorage()
            ) : Translator::trans('messages.select wishes'),
            [
                ButtonsStructure::BACK,
                ButtonsStructure::CANCEL_LAST_WISH,
                ButtonsStructure::GO_FOR_CASH
            ],
            ['config' => ButtonsFormatterService::ONE_TWO_DIALOG_MENU_FORMAT]
        );
        if ($second && Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage())) {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::GO_FOR_BONUSES]);
        }

        $wishService = new WishesService($wishes, $question, (new Options())->getWishes());
        $question = $wishService->addButtonsToQuestion();

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer);
            $this->bot->userStorage()->save(
                ['wishes' => collect($this->bot->userStorage()->get('wishes'))->push($answer->getText())]
            );
            $this->wishes(true);
        });
    }
}