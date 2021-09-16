<?php

namespace App\Services\Bot;

use App\Services\Translator;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use http\Exception\InvalidArgumentException;

/**
 * Более удобный класс для генерации вопросов чат-бота
 */
class ComplexQuestion extends Question
{
    /**
     * Создание вопроса вместе с кнопками но без переменных
     *
     * @param $text
     * @param array $buttonTexts
     * @return static
     */
    public static function createWithSimpleButtons($text, array $buttonTexts = []): ComplexQuestion
    {
        if(!$text) {
            throw new InvalidArgumentException('Попытка создания вопроса с пустым текстом');
        }

        $question = parent::create($text);
        foreach ($buttonTexts as $buttonText) {
            $value = array_get(explode('.', $buttonText), 1);
            $button = Button::create(Translator::trans($buttonText));
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