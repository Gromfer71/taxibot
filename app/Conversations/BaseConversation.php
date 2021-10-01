<?php

namespace App\Conversations;

use App\Conversations\FavoriteRoutes\AddedRouteMenuConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Models\Log;
use App\Models\User;
use App\Services\Address;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\Translator;
use App\Traits\UserManagerTrait;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Базовый класс диалога, от него наследуются все диалоги
 */
abstract class BaseConversation extends Conversation
{
    use UserManagerTrait;

    private
    const EMOJI = [
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
            $this->handleAction($answer->getValue());
        };
    }

    /**
     * @return User|User[]|Builder|Collection|Model|object
     */
    public function getUser()
    {
        return User::find($this->bot->getUser()->getId());
    }

    public function handleAction($value, $replaceActions = [])
    {
        $callbackOrMethodName = $this->getActions($replaceActions)[$value] ?? '';
        if (is_callable($callbackOrMethodName)) {
            $callbackOrMethodName();
            die();
        } elseif (method_exists($this, $callbackOrMethodName)) {
            $this->{$callbackOrMethodName}();
            die();
        } elseif (class_exists($callbackOrMethodName)) {
            $this->bot->startConversation(new $callbackOrMethodName());
            die();
        }
    }


    /**
     * Массив действий под определенную кнопку. Если значение это анонимная функция, то выполнится она, если имя метода,
     * то выполнится он в контексте текущего класса, если название класса (с полным путем), то запустится его Conversation.
     *
     * @param array $replaceActions
     * @return array
     */
    public function getActions(array $replaceActions = []): array
    {
        $actions = [];

        return array_replace_recursive($actions, $replaceActions);
    }

    public function _fallback($answer)
    {
        if ($answer->getValue() == 'add to favorite routes') {
            $this->bot->startConversation(new AddedRouteMenuConversation());
            die();
        }

        if ($answer->getValue() == 'aborted order' || $answer->getValue() == 'finished order') {
            $this->bot->startConversation(new StartConversation());
            return;
        }

        $className = get_class($this);
        $this->_sayDebug(
            'Ошибка - потерянный диалог в диалоге - ' . $className . ' text - ' . $answer->getText(
            ) . ' value - ' . $answer->getValue()
        );
        $this->_sayDebug('Возвращаемся к диалогу ' . $className);
        $this->bot->startConversation(new $className());
    }

    public function _sayDebug($message)
    {
        if (config('app.debug')) {
            $this->say(
                '||DEBUG|| ' . $message
            );
        }
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
        $question = Question::create($this->__('messages.thx for order'), $this->bot->getUser()->getId())
            ->addButtons([
                             Button::create('Продолжить')->value('Продолжить'),
                             Button::create(Translator::trans('buttons.add to favorite routes'))->value(
                                 'add to favorite routes'
                             ),
                         ]
            );

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'add to favorite routes') {
                $this->bot->startConversation(new AddedRouteMenuConversation());
            } else {
                Log::newLogAnswer($this->bot, $answer);
                $this->bot->startConversation(new MenuConversation());
            }
        });
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

            foreach ($this->getUser()->addresses as $historyAddressKey => $address) {
                $questionText .= $this->numberToEmodji($historyAddressKey + $key + 1) . ' ' . $address->address . "\n";
            }
        }

        return $questionText;
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


    /**
     * Упрощенный доступ в пользовательскому хранилищу (кешу)
     *
     * @param $key
     * @return Mixed
     */
    public function getFromStorage($key)
    {
        return $this->bot->userStorage()->get($key);
    }

    /**
     * Упрощенное сохранение в пользовательское хранилище
     *
     * @param array $data
     * @return Mixed
     */
    public function saveToStorage(array $data)
    {
        return $this->bot->userStorage()->save($data);
    }

    /**
     * Упрощенное удаление из хранилища. Null потому что delete() не работает во всех драйверах
     *
     * @param $key
     * @return Mixed
     */
    public function removeFromStorage($key)
    {
        return $this->bot->userStorage()->save([$key => null]);
    }

    public function navigationMapper()
    {
        return [];
    }

}