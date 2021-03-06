<?php

namespace App\Services\Bot;

use App\Services\Address;
use App\Services\Translator;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Storages\Storage;
use Illuminate\Support\Collection;

use function is_array;

/**
 * Более удобный класс для генерации вопросов чат-бота
 */
class ComplexQuestion extends Question
{
    /**
     * Создание вопроса вместе с кнопками, но без переменных
     *
     * @param $text
     * @param array $buttonTexts
     * @param array $additionalParameters
     * @return Question
     */
    public static function createWithSimpleButtons(
        $text,
        array $buttonTexts = [],
        array $additionalParameters = []
    ): Question {
        $question = new self($text);

        return self::setButtons($question, $buttonTexts, $additionalParameters);
    }

    /**
     * В случае, когда объект вопроса уже создан либо если нужно динамически по несколько раз добавлять кнопки, используется
     * этот метод, который просто добавляет кнопки к переданному вопросу
     *
     * @param $question
     * @param array $buttonTexts
     * @param array $additionalParameters
     * @param bool $withoutTrans
     * @return Question
     */
    public static function setButtons(
        $question,
        array $buttonTexts = [],
        array $additionalParameters = [],
        bool $withoutTrans = false
    ): Question {
        foreach ($buttonTexts as $buttonText) {
            if (is_array($buttonText)) {
                $buttonAdditional = $buttonText['additional'] ?? null;
                if ($buttonAdditional) {
                    $buttonText = $buttonText['text'] ?? '';
                }
            }

            $value = array_get(explode('.', $buttonText), 1);
            $button = Button::create(
                $withoutTrans ? $buttonText : Translator::trans($buttonText === '' ? '' : ('buttons.' . $buttonText))
            )->additionalParameters($buttonAdditional ?? $additionalParameters);
            if ($value) {
                $button->value($value);
            } else {
                $button->value($withoutTrans ? $buttonText : Translator::trans($buttonText));
            }
            $question->addButton($button);
        }

        return $question;
    }

    /**
     * Добавление кнопок в качестве адресов
     *
     * @param $question
     * @param \Illuminate\Support\Collection $addresses
     * @return \BotMan\BotMan\Messages\Outgoing\Question
     */
    public static function setAddressButtons($question, Collection $addresses): Question
    {
        foreach ($addresses as $key => $address) {
            $button = Button::create($address->address ?? $address)
                ->additionalParameters(['number' => $key + 1])
                ->value($address->address ?? $address);

            $question->addButton($button);
        }

        return $question;
    }

    public static function addOrderHistoryButtons($question, $orders, Storage $storage = null)
    {
        $num = 0;
        foreach ($orders as $order) {
            if ($num == Address::MAX_ADDRESSES_FOR_BUTTONS) {
                break;
            }
            $addressInfo = collect(json_decode($order->address, true));
            $addressInfo['address'] = array_filter($addressInfo['address']);
            if (count($addressInfo['address']) > 1) {
                $question->addButton(
                    Button::create(implode(' – ', $addressInfo->get('address')))->value(
                        implode(' – ', $addressInfo->get('address'))
                    )->additionalParameters(['number' => $num + 1])
                );
                $num++;
            }
            if ($storage) {
                $storage->save(['crews' => collect($storage->get('crews'))->put(implode(' – ', $addressInfo->get('address')), json_decode($order->state, false)->order_crew_group_id ?? null)]);
            }
        }


        return $question;
    }

    public static function addFavoriteRoutesButtons($question, $routes)
    {
        foreach ($routes as $route) {
            $question->addButton(Button::create($route->name));
        }

        return $question;
    }


}