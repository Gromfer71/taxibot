<?php


namespace App\Conversations;


use App\Models\AddressHistory;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Address;
use App\Services\ButtonsFormatterService;
use App\Services\MessageGeneratorService;
use App\Services\Options;
use App\Services\OrderApiService;
use App\Services\Price;
use App\Services\WishesService;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Artisan;

class TaxiMenuConversation extends BaseAddressConversation
{
    public function _go_for_cash(){
        OrderHistory::newOrder($this->bot);
        $this->currentOrderMenu(true);
    }

    public function _go_for_bonuses(){
        if(User::find($this->bot->getUser()->getId())->getBonusBalance() > 0) {
            $this->bot->userStorage()->save(['usebonus' => true]);
            $this->bot->userStorage()->save(['bonusbalance' => User::find($this->bot->getUser()->getId())->getBonusBalance()]);
            OrderHistory::newOrder($this->bot, true);
        } else {
            $this->say(trans('messages.zero bonus balance'), $this->bot->getUser()->getId());
            OrderHistory::newOrder($this->bot, false);
        }
        $this->currentOrderMenu(true);
    }

    public function run()
    {
        $this->menu();
    }

    public function menu()
    {
        $this->_sayDebug('TaxiMenuConversation - menu');
        $this->calcPrice();
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage());

        $question = Question::create(MessageGeneratorService::getFullOrderInfoFromStorage($this->bot->userStorage()), $this->bot->getUser()->getId());
        if ($haveEndAddress){
            $question = $question->addButtons(
                [
                    Button::create(trans('buttons.exit to menu'))->additionalParameters(['config' => ButtonsFormatterService::TAXI_MENU_FORMAT])->value('exit to menu'),
                    Button::create(trans('buttons.add address'))->value('add address'),
                    Button::create(trans('buttons.go for cash'))->value('go for cash'),
                    Button::create(trans('buttons.write comment'))->value('write comment'),
                    Button::create(trans('buttons.go for bonuses'))->value('go for bonuses'),
                    Button::create(trans('buttons.wishes'))->value('wishes'),
                    Button::create(trans('buttons.change price'))->value('change price')
                ]
            );
        } else {
            $question = $question->addButtons(
                [
                    Button::create(trans('buttons.exit to menu'))->additionalParameters(['config' => ButtonsFormatterService::TAXI_MENU_FORMAT])->value('exit to menu'),
                    Button::create(trans('buttons.write comment'))->value('write comment'),
                    Button::create(trans('buttons.go for cash'))->value('go for cash'),
                    Button::create(trans('buttons.wishes'))->value('wishes'),
                    Button::create(trans('buttons.change price'))->value('change price')
                ]
            );
        }

        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'add address') {
                        $this->addAdditionalAddress();
                    } elseif ($answer->getValue() == 'go for cash') {
                      $this->_go_for_cash();
                    } elseif ($answer->getValue() == 'go for bonuses') {
                       $this->_go_for_bonuses();
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->bot->startConversation(new MenuConversation());
                    } elseif ($answer->getValue() == 'change price') {
                        $this->changePrice();
                    } elseif ($answer->getValue() == 'write comment') {
                        $this->writeComment();
                    } elseif ($answer->getValue() == 'client_goes_out') {
                       return;
                    } elseif ($answer->getValue() == 'wishes') {
                    $this->wishes();
                }
                } else {
                    $this->menu();
                }
            }
        );
    }

    public function calcPrice()
    {
        $options = new Options($this->bot->userStorage());
        $crewGroupId = collect($this->bot->userStorage()->get('crew_group_id'))->first();
        $this->_sayDebug('crewGroupId из первого адреса - '.$crewGroupId);

        if (!$crewGroupId){
            $city =  User::find($this->bot->getUser()->getId())->city;
            $this->_sayDebug('crewGroupId из первого адреса не найдено город  - '.$city);
            $crewGroupId = $options->getCrewGroupIdFromCity($city ?? null);
        }
        $api = new OrderApiService();
        $tariff = $api->selectTariffForOrder($crewGroupId, $this->bot->userStorage()->get('lat'), $this->bot->userStorage()->get('lon'));
        $priceResponse = $api->calcOrderPrice($tariff->data->tariff_id, $options->getOrderParamsArray($this->bot->userStorage()), $this->bot->userStorage());
        $this->_sayDebug('Цена поездки  - '.json_encode($priceResponse,JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save(['price' => $priceResponse->data->sum ?? 101]);
        $this->bot->userStorage()->save(['tariff_id' => $tariff->data->tariff_id]);
        $this->bot->userStorage()->save(['crew_group_id' => $crewGroupId]);
    }

    public function addAdditionalAddress()
    {
        $this->_sayDebug('addAdditionalAddress');
        $message = trans('messages.add additional address');
        $message = $this->addAddressesToMessage($message);
        $question = Question::create($message, $this->bot->getUser()->getId());
        $question = $question->addButton(Button::create(trans('buttons.back'))->value('back')->additionalParameters(['location' => 'addresses']));
        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);
        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->menu();
                        return;
                    }
                }

                $address = $this->_getAddressFromHistoryByAnswer($answer);

                if ($address) {
                    if ($address['lat'] == 0 && Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage())){
                        $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
                    }
                    $this->_saveAnotherAddress($address->address, $address['lat'], $address['lon']);
                    $this->menu();

                } else {
                    Log::newLogAnswer($this->bot, $answer);
                    $this->_saveAnotherAddress($answer);
                    $addressesList = collect(Address::getAddresses($answer->getText(), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
                    if ($addressesList->isEmpty()) {
                        $this->streetNotFoundAdditionalAddress();
                        return;
                    } else {
                        $this->addAdditionalAddressAgain();
                        return;
                    }
                }
            }
        );
    }

    public function addAdditionalAddressAgain()
    {
        $this->_sayDebug('addAdditionalAddressAgain');


        $addressesList = collect(Address::getAddresses(collect($this->bot->userStorage()->get('address'))->last(), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()))->take(25)->values();
        $questionText = $this->addAddressesFromApi(trans('messages.give address again'), $addressesList);
        $question = Question::create($questionText, $this->bot->getUser()->getId());
        $question->addButton(Button::create(trans('buttons.back'))->value('back')->additionalParameters(['location' => 'addresses']));
        if ($addressesList->isNotEmpty()) {
            foreach ($addressesList as $key => $address) {
                $question->addButton(Button::create(Address::toString($address))->value(Address::toString($address))->additionalParameters(['number' => $key + 1]));
            }
        } else {
            $this->streetNotFoundAdditionalAddress();
            return;
        }
        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->getValue() == 'back') {
                    $this->_forgetLastAddress();
                    $this->menu();
                    return;
                }
                $address = Address::findByAnswer($addressesList, $answer);
                $this->_sayDebug('addAdditionalAddressAgain - address -' . json_encode($address, JSON_UNESCAPED_UNICODE));

                if ($address) {
                    if ($address['kind'] == 'street') {
                        $this->_saveAnotherAddress($address['street'], 0, 0, true);
                        $this->forgetWriteHouseAdditionalAddress();
                        return;
                    }

                    AddressHistory::newAddress($this->getUser()->id, $answer->getText(), $address['coords'], $address['city']);

                    $this->_saveAnotherAddress($answer, $address['coords']['lat'], $address['coords']['lon'], true);
                    $this->menu();
                } else {
                    $this->_saveAnotherAddress($answer, 0, 0, true);
                    $this->addAdditionalAddressAgain();
                }
            }
        );
    }

    public function streetNotFoundAdditionalAddress()
    {
        $this->_sayDebug('streetNotFoundAdditionalAddress');
        $question = Question::create(trans('messages.not found address'), $this->bot->getUser()->getId());
        $question->addButtons(
            [
                Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT])->value('back'),
                Button::create(trans('buttons.go as indicated'))->value('go as indicated'),
                Button::create(trans('buttons.exit to menu'))->value('exit to menu'),
            ]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->_forgetLastAddress();
                        $this->addAdditionalAddress();
                        return;
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->bot->startConversation(new MenuConversation());
                    } elseif ($answer->getValue() == 'go as indicated') {
                        $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
                        $this->bot->startConversation(new TaxiMenuConversation());
                        return;
                    }
                } else {
                    $this->_saveAnotherAddress($answer, 0, 0, true);
                    $this->addAdditionalAddressAgain();
                }
            }
        );
    }

    public function forgetWriteHouseAdditionalAddress()
    {
        $this->_sayDebug('forgetWriteHouseAdditionalAddress');
        $question = Question::create(trans('messages.forget write house'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create(trans('buttons.exit'))->value('exit'),
            ]);;

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->_forgetLastAddress();
                    $this->bot->startConversation(new MenuConversation());
                    return;
                }
            }


            $this->_sayDebug('forgetWriteHouseAdditionalAddress - дополнительный адрес');

            $this->_addToLastAnotherAddress($answer);

            $this->addAdditionalAddressAgain();

        });
    }


    public function currentOrderMenu($withMessageAboutOrderCreated = null,$exactlyWithoutMessage = false)
    {
        $this->_sayDebug('currentOrderMenu');
        if ($withMessageAboutOrderCreated) {
            $text = MessageGeneratorService::getFullOrderInfoFromStorage2($this->bot->userStorage());
        } else {
            $text = MessageGeneratorService::getFullOrderInfoFromStorage($this->bot->userStorage());
        }
        if ($exactlyWithoutMessage){
            $text = '';
        }

        $question = Question::create($text, $this->bot->getUser()->getId())
            ->addButtons(
                [
                    Button::create(trans('buttons.need dispatcher'))->additionalParameters(['config' => ButtonsFormatterService::CURRENT_ORDER_MENU_FORMAT])->value('need dispatcher'),
                    Button::create(trans('buttons.order info'))->value('order info'),
                    Button::create(trans('buttons.cancel order'))->value('cancel order'),
                    Button::create(trans('buttons.change price'))->value('change price'),
                ]
            );




        return $this->ask(
            $question,
            function (Answer $answer) {
                $api = new OrderApiService();
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    $this->_sayDebug($answer->getValue());
                    if ($answer->getValue() == 'cancel order') {
                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $this->_sayDebug('Сохраняем отмену заказа');
                        if ($order) $order->cancelOrder();
                        $this->cancelOrder();
                    } elseif ($answer->getValue() == 'order info') {
                        $this->_sayDebug('order info - execute');
                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $newStateKind = $order->checkOrder();
                        if (!$newStateKind){
                            $this->say(trans('messages.pls wait we are searching auto now'), $this->bot->getUser()->getId());
                            $this->currentOrderMenu(true,true);                        }
                    } elseif ($answer->getValue() == 'change price') {
                        $this->changePriceInOrderMenu();
                    } elseif ($answer->getValue() == 'need dispatcher') {
                        $this->getUser()->setUserNeedDispatcher();
						$this->say(trans('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                        $this->currentOrderMenu(true,true);
                    } elseif ($answer->getValue() == 'order_confirm') {
                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        if ($order) $order->confirmOrder();
                        $this->bot->startConversation(new DriverAssignedConversation());
                    } elseif($answer->getValue() == 'client_goes_out') {
                        $order = \App\Models\OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                        $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    } elseif($answer->getValue() == 'client_goes_out_late') {
                        $order = \App\Models\OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                        $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    } elseif ($answer->getValue() == 'order_cancel') {
                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $this->say('Ваш заказ отменен. Очень хочу надеяться, что Вы ко мне ещё вернётесь.');
                        if ($order) $order->cancelOrder();
                        $this->bot->startConversation(new StartConversation());
                    } elseif($answer->getValue() == 'need driver') {
                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        if ($order) $api->connectClientAndDriver($order);
                        $this->say(trans('messages.connect with driver'), $this->bot->getUser()->getId());
                        $this->currentOrderMenu(true,true);
                    } elseif ($answer->getValue() == 'finish order') {
                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        if ($order) $order->finishOrder();
                        $this->bot->say(trans('messages.thx for order'),$this->bot->getUser()->getId());
                        $this->bot->startConversation(new \App\Conversations\StartConversation());
                    } elseif ($answer->getValue() == 'need dispatcher') {
                        $this->getUser()->setUserNeedDispatcher();
                        $this->say(trans('messages.wait for dispatcher'),$this->bot->getUser()->getId());
                        $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    }  else {
                       $this->_fallback($answer);
                    }
                } else {
                    $this->currentOrderMenu(true,true);
                }

            }
        );
    }

    public function cancelOrder()
    {
        $this->_sayDebug('TaxiMenuConversation cancelOrder');
        $question = Question::create(trans('messages.cancel order'), $this->bot->getUser()->getId())
            ->addButton(Button::create(trans('buttons.continue'))->value('continue'));

        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                $this->bot->startConversation(new MenuConversation());
            }
        );
    }

    public function changePriceInOrderMenu()
    {
        $question = Question::create(trans('messages.current price', ['price' => $this->bot->userStorage()->get('price')]), $this->bot->getUser()->getId());
        $prices = (new Options($this->bot->channelStorage()))->getChangePriceOptionsInOrderMenu();

        $prices = $this->_filterChangePrice($prices,'changed_price_in_order');
        $question = $this->_addChangePriceDefaultButtons($question);
        $question = Price::getChangePrice($question, $prices);
        return $this->ask(
            $question,
            function (Answer $answer) use ($prices) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->currentOrderMenu();
                        return;
                    }
                    if ($answer->getValue() == 'cancel change price') {

                        $this->_sayDebug(json_encode($this->bot->userStorage()->get('changed_price_in_order'), JSON_UNESCAPED_UNICODE));

                        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $order->changed_price = null;
                        $order->save();
                        $this->bot->userStorage()->save(['changed_price_in_order' => null,'price' => $order->price]);
                        $order->changePrice($this->bot);
                        $this->currentOrderMenu();
                        return;
                    } elseif ($answer->getValue() == 'cancel order') {
                    $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    $this->_sayDebug('Сохраняем отмену заказа');
                    if ($order) $order->cancelOrder();
                    $this->cancelOrder();
                    return;
                }  elseif ($answer->getValue() == 'need dispatcher') {
					  $this->say(trans('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                        $this->getUser()->setUserNeedDispatcher();
                        $this->currentOrderMenu(true,true);
                    return;
                } elseif ($answer->getValue() == 'order_confirm') {
                    $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    if ($order) $order->confirmOrder();
                    $this->bot->startConversation(new DriverAssignedConversation());
                        return;
                } elseif($answer->getValue() == 'client_goes_out') {
                    $order = \App\Models\OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    $api = new OrderApiService();
                    $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                    $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                        return;
                } elseif($answer->getValue() == 'client_goes_out_late') {
                    $order = \App\Models\OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    $api = new OrderApiService();
                    $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                    $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    return;
                } elseif ($answer->getValue() == 'order_cancel') {
                    $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    $this->say('Ваш заказ отменен. Очень хочу надеяться, что Вы ко мне ещё вернётесь.');
                    if ($order) $order->cancelOrder();
                    $this->bot->startConversation(new StartConversation());
                    return;
                } elseif($answer->getValue() == 'need driver') {
                    $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    $api = new OrderApiService();
                    if ($order) $api->connectClientAndDriver($order);
                    $this->say(trans('messages.connect with driver'), $this->bot->getUser()->getId());
                    $this->currentOrderMenu(true,true);
                    return;
                } elseif ($answer->getValue() == 'finish order') {
                    $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                    if ($order) $order->finishOrder();
                    $this->bot->say(trans('messages.thx for order'),$this->bot->getUser()->getId());
                    $this->bot->startConversation(new \App\Conversations\StartConversation());
                    return;
                } elseif ($answer->getValue() == 'need dispatcher') {
                    $api = new OrderApiService();
					$this->say(trans('messages.wait for dispatcher'),$this->bot->getUser()->getId());
                    $this->getUser()->setUserNeedDispatcher();
                    $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    return;
                }
                }
                $this->_sayDebug(json_encode($prices, JSON_UNESCAPED_UNICODE));
                $this->_sayDebug($answer->getText());

                $price = collect($prices)->filter(function ($item) use ($answer) {
                    if ($item->description == $answer->getText()) {
                        return $item;
                    }
                })->first();
                if (!$price) {
                    $this->changePriceInOrderMenu();
                    return;
                }
                $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                $order->changed_price = (int)$price->id;

                $order->save();
                $this->bot->userStorage()->save(['changed_price_in_order' => $price,'price' => $order->price + $price->value]);
                $order->changePrice($this->bot);

                $this->currentOrderMenu();


            }
        );
    }

    public function changePrice()
    {
        $question = Question::create(trans('messages.current price', ['price' => $this->bot->userStorage()->get('price')]), $this->bot->getUser()->getId());
        $options = new Options($this->bot->channelStorage());
        $prices = $options->filterChangePriceOptions(User::find($this->bot->getUser()->getId())->city);

        $prices = $this->_filterChangePrice($prices);

        $this->_sayDebug(json_encode($prices, JSON_UNESCAPED_UNICODE));
        $question = $this->_addChangePriceDefaultButtons($question);
        $question = Price::getChangePrice($question, $prices);

        return $this->ask(
            $question,
            function (Answer $answer) use ($options, $prices) {
                Log::newLogAnswer($this->bot, $answer);
                    if ($answer->getValue() == 'back') {
                        $this->menu();
                        return;
                    } elseif ($answer->getValue() == 'cancel change price') {
                        $this->bot->userStorage()->save(['changed_price' => null]);
                        $this->_sayDebug(json_encode($this->bot->userStorage()->get('changed_price'), JSON_UNESCAPED_UNICODE));
                        $this->menu();
                        return;
                    } else {
                        $this->_sayDebug(json_encode($prices, JSON_UNESCAPED_UNICODE));
                        $this->_sayDebug($answer->getText());

                        $price = collect($prices)->filter(function ($item) use ($answer) {
                            if ($item->description == $answer->getText()) {
                                return $item;
                            }
                        })->first();
                        if (!$price) {
                            $this->changePrice();
                            return;
                        }
                        $this->_sayDebug('Выбрано изменение цены' . json_encode($price, JSON_UNESCAPED_UNICODE));
                        $this->bot->userStorage()->save(['changed_price' => $price]);

                        $this->menu();
                    }
            }
        );
    }

    public function writeComment()
    {
        $question = Question::create(trans('messages.write comment or choose'), $this->bot->getUser()->getId());
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage());
        if ($haveEndAddress) {
            $question = $question->addButtons([Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::COMMENT_MENU_FORMAT])->value('back'),
                                               Button::create(trans('buttons.go for bonuses'))->value('go for bonuses'),
                                               Button::create(trans('buttons.go for cash'))->value('go for cash')]);
        } else {
            $question = $question->addButtons([Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::SPLITBYTWO_MENU_FORMAT]),
                                               Button::create(trans('buttons.go for cash'))->value('go for cash')]);
        }


        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'go for cash') {
                        $this->_go_for_cash();
                    } elseif ($answer->getValue() == 'go for bonuses') {
                        $this->_go_for_bonuses();
                    } elseif ($answer->getValue() == 'back') {
                        $this->menu();
                    }
                } else {
                    $this->bot->userStorage()->save(['comment' => $answer->getText()]);
                    $this->menuAfterWrittenComment();
                }
            }
        );
    }

    public function menuAfterWrittenComment()
    {
        $data = [
            'route' => collect($this->bot->userStorage()->get('address'))->implode(' - '),
            'address' => collect($this->bot->userStorage()->get('address'))->first(),
            'price' => $this->bot->userStorage()->get('price'),
            'comment' => $this->bot->userStorage()->get('comment'),
        ];



        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage());

        if ($haveEndAddress) {
            $question = Question::create(trans('messages.order info with comment',$data),$this->bot->getUser()->getId());
            $question = $question->addButtons([Button::create(trans('buttons.go for bonuses'))->additionalParameters(['config' => ButtonsFormatterService::SPLITBYTWO_MENU_FORMAT])->value('go for bonuses'),
                Button::create(trans('buttons.go for cash'))->value('go for cash'),
                Button::create(trans('buttons.back'))->value('back'),
                Button::create(trans('buttons.wishes'))->value('wishes')]);

        } else {
            $question = Question::create(trans('messages.komment_i_pozhelanie_skazhu_voditelu_punkt_6',$data),$this->bot->getUser()->getId());
            $question = $question->addButtons([Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT])->value('back'),
                Button::create(trans('buttons.wishes'))->value('wishes'),
                Button::create(trans('buttons.go for cash'))->value('go for cash')
                ]);
        }


        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'go for cash') {
                        $this->_go_for_cash();
                    }  elseif ($answer->getValue() == 'go for bonuses') {
                        $this->_go_for_bonuses();
                    } elseif ($answer->getValue() == 'back') {
                        $this->menu();
                    } elseif ($answer->getValue() == 'wishes') {
                        $this->wishes();
                    }
                } else {
                    $this->bot->userStorage()->save(['comment' => $answer->getText()]);
                    $this->menuAfterWrittenComment();
                }
            }
        );
    }

    public function wishes($second = false)
    {
        $this->_sayDebug('wishes - start');
        $this->calcPrice();
        $this->_sayDebug('wishes - after -calc price');
        $wishes = collect($this->bot->userStorage()->get('wishes'));
        if ($second){
            $question = Question::create(MessageGeneratorService::getFullOrderInfoFromStorage($this->bot->userStorage()), $this->bot->getUser()->getId());
        } else {
            $question = Question::create(trans('messages.select wishes'), $this->bot->getUser()->getId());
        }


        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($this->bot->userStorage());
        if ($haveEndAddress) {
            $question = $question->addButtons([Button::create(trans('buttons.go for cash'))->additionalParameters(['config' => ButtonsFormatterService::ONE_TWO_DIALOG_MENU_FORMAT])->value('go for cash'),
                                               Button::create(trans('buttons.go for bonuses'))->value('go for bonuses'),
                                               Button::create(trans('buttons.back'))->value('back'),
                                               Button::create(trans('buttons.cancel last wish'))->value('cancel last wish')
                ]);
        } else {
            $question = $question->addButtons([Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::ONE_TWO_DIALOG_MENU_FORMAT])->value('back'),
                Button::create(trans('buttons.go for cash'))->value('go for cash'),
                Button::create(trans('buttons.cancel last wish'))->value('cancel last wish')
            ]);
        }


        $options = new Options($this->bot->channelStorage());

        $wishService = new WishesService($wishes, $question, $options->getWishes());
        $wishService->addButtonsToQuestion();
        $question = $wishService->getQuestion();


        $this->_sayDebug('wishes before ask');

        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'go for cash') {
                        $this->_go_for_cash();
                        return;
                    } elseif ($answer->getValue() == 'go for bonuses') {
                        $this->_go_for_bonuses();
                        return;
                    } elseif ($answer->getValue() == 'cancel last wish') {
                        $wishes = collect($this->bot->userStorage()->get('wishes'));
                        $wishes->pop();
                        $this->bot->userStorage()->save(['wishes' => $wishes]);
                        $this->wishes(true);
                        return;
                    } elseif ($answer->getValue() == 'back') {
                        $this->menu();
                        return;
                    }
                }
                $this->bot->userStorage()->save(['wishes' => collect($this->bot->userStorage()->get('wishes'))->push($answer->getText())]);
                $this->wishes(true);

            }
        );
    }
}