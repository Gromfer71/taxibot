<?php


namespace App\Services;


use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class Price
{
		public static function getChangePrice(Question $question, $prices)
		{
			foreach ($prices as $price) {
				$question = $question->addButton(Button::create($price->description));
			}

			return $question;
		}
}