<?php

namespace App\Services\Bot;

use App\Services\Translator;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Facades\Log;

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
    public static function createWithSimpleButtons($text, array $buttonTexts = [], array $additionalParameters = [])
    {
        $question = parent::create($text);

        return self::setButtonsArrayToExistQuestion($question, $buttonTexts, $additionalParameters);
    }

    public static function setButtonsArrayToExistQuestion($question, array $buttonTexts = [], $additionalParameters = [])
    {
        Log::info(json_encode($buttonTexts, JSON_UNESCAPED_UNICODE));
        foreach ($buttonTexts as $buttonText) {
            $value = array_get(explode('.', $buttonText), 1);
            $button = Button::create(Translator::trans($buttonText))->additionalParameters($additionalParameters);
            if($value) {
                $button->value($value);
            } else {
                $button->value(Translator::trans($buttonText));
            }
            $question->addButton($button);
        }

        return $question;
    }
}