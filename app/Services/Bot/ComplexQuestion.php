<?php

namespace App\Services\Bot;

use App\Services\Translator;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Collection;

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
     * @return static
     */
    public static function createWithSimpleButtons(
        $text,
        array $buttonTexts = [],
        array $additionalParameters = []
    ): ComplexQuestion {
        $question = parent::create($text);
        $buttonTexts = array_map(function ($item) {
            return 'buttons.' . $item;
        }, $buttonTexts);

        return self::setButtons($question, $buttonTexts, $additionalParameters);
    }

    /**
     * В случае, когда объект вопроса уже создан либо если нужно динамически по несколько раз добавлять кнопки, используется
     * этот метод, который просто добавляет кнопки к переданному вопросу
     *
     * @param $question
     * @param array $buttonTexts
     * @param array $additionalParameters
     * @return mixed
     */
    public static function setButtons(
        $question,
        array $buttonTexts = [],
        array $additionalParameters = []
    ) {
        foreach ($buttonTexts as $buttonText) {
            $value = array_get(explode('.', $buttonText), 1);
            $button = Button::create(Translator::trans($buttonText))->additionalParameters($additionalParameters);
            if ($value) {
                $button->value($value);
            } else {
                $button->value(Translator::trans($buttonText));
            }
            $question->addButton($button);
        }

        return $question;
    }

    /**
     * @param $question
     * @param \Illuminate\Support\Collection $addresses
     * @return mixed
     */
    public static function setAddressButtons($question, Collection $addresses)
    {
        foreach ($addresses as $key => $address) {
            $button = Button::create($address->address)
                ->additionalParameters(['number' => $key + 1])
                ->value($address->address);

            $question->addButton($button);
        }

        return $question;
    }


}