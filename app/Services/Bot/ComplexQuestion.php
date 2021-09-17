<?php

namespace App\Services\Bot;

use App\Services\Translator;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

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

        return self::setButtonsArrayToExistQuestion($question, $buttonTexts, $additionalParameters);
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
    public static function setButtonsArrayToExistQuestion(
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
}