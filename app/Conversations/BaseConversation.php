<?php

namespace App\Conversations;

use App\Conversations\FavoriteRoutes\AddedRouteMenuConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Models\Config;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\DadataAddress;
use App\Services\Options;
use App\Services\OrderApiService;
use App\Services\Translator;
use App\Traits\UserManagerTrait;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;

/**
 * Базовый класс диалога, от него наследуются все диалоги
 */
abstract class BaseConversation extends Conversation
{
    use UserManagerTrait;

    public const EMOJI = [
        '0' => '0&#8419;',
        '1' => '1&#8419;',
        '2' => '2&#8419;',
        '3' => '3&#8419;',
        '4' => '4&#8419;',
        '5' => '5&#8419;',
        '6' => '6&#8419;',
        '7' => '7&#8419;',
        '8' => '8&#8419;',
        '9' => '9&#8419;',
        '10' => '10&#8419;',
    ];

    protected $options;
    protected $user;

    public function __construct()
    {
        $this->options = new Options();
    }

    public function getDefaultCallback()
    {
        return function (Answer $answer) {
            $this->handleAction($answer) ?: $this->run();
        };
    }

    public function handleAction($answer, $replaceActions = [])
    {
        if (!$this->getUser()) {
            $this->bot->startConversation(new StartConversation());
            die();
        }

        if (Translator::trans('buttons.' . $answer->getValue()) != 'buttons.') {
            $value = Translator::trans('buttons.' . $answer->getValue());
        }
        Log::newLogAnswer($this->getUser()->id ?? null, $answer->getText(), $value ?? null);
        $callbackOrMethodName = $this->getActions($replaceActions)[$answer->getValue()] ?? '';

        if (is_callable($callbackOrMethodName)) {
            $callbackOrMethodName();
            return true;
        } elseif (method_exists($this, $callbackOrMethodName)) {
            $this->{$callbackOrMethodName}();
            return true;
        } elseif (class_exists($callbackOrMethodName)) {
            $this->bot->startConversation(new $callbackOrMethodName());
            return true;
        }

        return false;
    }

    public function getUser()
    {
        return User::find($this->bot->getUser()->getId());
    }

    public function getActions($replaceActions = []): array
    {
        $actions = [];

        return array_replace_recursive($actions, $replaceActions);
    }

    public function _filterChangePrice($prices, $key_price = 'changed_price')
    {
        if ($this->bot->userStorage()->get($key_price)) {
            $changedPriceId = $this->bot->userStorage()->get($key_price)['id'];
            foreach ($prices as $key => $price) {
                if ($price->id == $changedPriceId) {
                    unset($prices[$key]);
                }
            }
        }
        return $prices;
    }

    public function _addChangePriceDefaultButtons($question)
    {
        $question->addButton(
            Button::create($this->__('buttons.back'))->additionalParameters(
                ['config' => ButtonsFormatterService::CHANGE_PRICE_MENU_FORMAT]
            )->value('back')
        );
        $question->addButton(Button::create($this->__('buttons.cancel change price'))->value('cancel change price'));
        return $question;
    }

    public function __($key, $replace = [])
    {
        return Translator::trans($key, $replace);
    }

    public function end()
    {
        $question = Question::create($this->__('messages.thx for order'));
        if (!$this->bot->userStorage()->get('second_address_will_say_to_driver_flag') && !$this->bot->userStorage()->get('is_route_from_favorite')) {
            $question->addButton(
                Button::create(Translator::trans('buttons.add to favorite routes'))->value(
                    'add to favorite routes'
                )
            );
        }
        $question->addButton(Button::create(Translator::trans('buttons.exit to menu'))->value('exit to menu'));

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'add to favorite routes') {
                $this->bot->userStorage()->save(['order_already_done' => true]);
                $this->bot->startConversation(new AddedRouteMenuConversation());
            } elseif ($answer->getValue() == ButtonsStructure::EXIT_TO_MENU) {
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->end();
            }
        });
    }

    public function ask($question, $next, $additionalParameters = [])
    {
        Log::newLogDebug($this->getUser()->id ?? null, $question->getText());
        $this->bot->reply($question, $additionalParameters);

        if (isset($additionalParameters['welcome_message'])) {
            if (get_class($this->bot->getDriver()) === TelegramDriver::class && $file = Config::where('name', 'welcome_file_telegram')->first()) {
                $this->say(OutgoingMessage::create('', new File(env('APP_URL') . 'storage/telegram/' . $file->value)));
            } elseif (get_class($this->bot->getDriver()) === VkCommunityCallbackDriver::class && $file = Config::where('name', 'welcome_file_vk')->first()) {
                $this->say(OutgoingMessage::create('', new File(env('APP_URL') . 'storage/vk/' . $file->value)));
            }
        }

        $this->bot->storeConversation($this, $next, $question, $additionalParameters);

        return $this;
    }

    public function addAddressesToMessage($questionText)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $questionText .= "\n";
            $this->_sayDebug('property exists');
            foreach ($this->getUser()->favoriteAddresses as $key => $address) {
                $questionText .= $this->numberToEmodji($key + 1) . '⭐️' . $address->name . "\n";
            }

            $key = $this->getUser()->favoriteAddresses->count();

            foreach ($this->getUser()->addresses->take(BaseAddressConversation::ADDRESSES_HISTORY_COUNT - $key) as $historyAddressKey => $address) {
                $questionText .= $this->numberToEmodji($historyAddressKey + $key + 1) . ' ' . $address->address . "\n";
            }
        }

        return $questionText;
    }

    public function _sayDebug($message)
    {
        if (config('app.debug')) {
            $this->say(
                '||DEBUG|| ' . $message
            );
        }
    }

    public function numberToEmodji($number)
    {
        $number = (string)$number;
        $number = str_split($number);
        $result = '';
        foreach ($number as $item) {
            $result .= self::EMOJI[$item];
        }

        return $result;
    }

    public function addAddressesToMessageOnlyFromHistory($questionText)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $questionText .= "\n";

            foreach ($this->getUser()->addresses as $historyAddressKey => $address) {
                $questionText .= $this->numberToEmodji($historyAddressKey + 1) . ' ' . $address->address . "\n";
            }
        }

        return $questionText;
    }

    public function addAddressesFromApi($questionText, $addresses)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $questionText .= "\n";
            $addresses = $addresses->values();

            foreach ($addresses as $key => $address) {
                $questionText .= self::numberToEmodji($key + 1) . ' ' . Address::toString($address) . "\n";
            }
        }

        return $questionText;
    }

    public function removeFromStorage($key)
    {
        return $this->bot->userStorage()->save([$key => null]);
    }

    public function addOrdersRoutesToMessage($message)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $num = 0;
            $message .= "\n";
            foreach ($this->getUser()->orders as $order) {
                if ($num == Address::MAX_ADDRESSES_FOR_BUTTONS) {
                    break;
                }
                $addressInfo = collect(json_decode($order->address, true));
                $addressInfo['address'] = array_filter($addressInfo['address']);
                if (count($addressInfo['address']) > 1) {
                    $text = self::numberToEmodji($num + 1) . ' ' . implode(
                            ' – ',
                            $addressInfo->get('address')
                        );
                    $textForSave = implode(
                        ' – ',
                        $addressInfo->get('address')
                    );
                    $message .= $text . "\n";
                    $this->saveToStorage(['address_in_number' => collect($this->getFromStorage('address_in_number'))->put($num + 1, $textForSave)]);
                    $num++;
                }
            }
        }

        return $message;
    }

    public function saveToStorage(array $data)
    {
        return $this->bot->userStorage()->save($data);
    }

    public function getFromStorage($key)
    {
        return $this->bot->userStorage()->get($key);
    }

    public function getChangePrice(Question $question, $prices)
    {
        foreach ($prices as $price) {
            $question = $question->addButton(
                Button::create(Translator::trans('buttons.change price #' . $price->id))
                    ->value('change price #' . $price->id)
            );
        }

        return $question;
    }

    public function getQuestionInOrderFromCron()
    {
        $actualOrder = OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName());
        if(!$actualOrder) {
            return null;
        }
        $orderStatus = $actualOrder->getCurrentOrderState();

        if ($orderStatus->state_id == OrderHistory::DRIVER_ASSIGNED) {
            $api = new OrderApiService();
            $time = $api->driverTimeCount($actualOrder->id)->data->DRIVER_TIMECOUNT;
            $auto = $actualOrder->getAutoInfo();
            $question = ComplexQuestion::createWithSimpleButtons(
                Translator::trans('messages.auto info with time', ['time' => $time, 'auto' => $auto]),
                [ButtonsStructure::CANCEL_ORDER, ButtonsStructure::ORDER_CONFIRM],
                ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
            );
        } elseif ($orderStatus->state_id == OrderHistory::CAR_AT_PLACE) {
            $question = ComplexQuestion::createWithSimpleButtons(
                Translator::trans(
                    'messages.auto waits for client',
                    ['auto' => $actualOrder->getAutoInfo()]
                ),
                [ButtonsStructure::CANCEL_ORDER, ButtonsStructure::CLIENT_GOES_OUT],
                ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
            );
        }

        return $question ?? null;
    }

    public function getActualOrderStateId()
    {
        $actualOrder = OrderHistory::getActualOrder($this->getUser()->id, $this->bot->getDriver()->getName());
        $orderStatus = $actualOrder->getCurrentOrderState();

        return $orderStatus->state_id ?? null;
    }

    public function getLocation($answer)
    {
        if ($answer instanceof Location) {
            $coords = [
                'lat' => $answer->getLatitude(),
                'lon' => $answer->getLongitude(),
            ];
        } else {
            $coords = [
                'lat' => $answer->getMessage()->getLocation()->getLatitude(),
                'lon' => $answer->getMessage()->getLocation()->getLongitude(),
            ];
        }

        $address = DadataAddress::getAddressByCoords($coords['lat'], $coords['lon']);
        if (!$address) {
            $this->say(Translator::trans('messages.address location error'));
            die();
        }
        $this->saveToStorage(['got_location' => true]);

        return $address;
    }
}