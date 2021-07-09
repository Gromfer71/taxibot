<?php

namespace App\Conversations;

use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;

class StartConversation extends BaseConversation
{
	public function run()
	{
		$user = User::find($this->bot->getUser()->getId());
		if (! $user) {
			$this->start();
		} elseif (!OrderHistory::getActualOrder($user->id)) {
			$this->bot->startConversation(new MenuConversation());
		}

	}


	public function start()
	{
		if(!User::find($this->bot->getUser()->getId())) {
			$user = User::create([
				'username' => $this->bot->getUser()->getUsername(),
				'firstname' => $this->bot->getUser()->getFirstName(),
				'lastname' => $this->bot->getUser()->getLastName(),
				'userinfo' => json_encode($this->bot->getUser()->getInfo()),
			]);
		    $user->setPlatformId($this);

			$user->save();
		}

		$this->checkConfig();

		$question = Question::create(trans('messages.welcome message'), $this->bot->getUser()->getId())
			->addButtons(
				[
					Button::create(trans('buttons.start menu'))->value('start menu'),
				]
			);

		return $this->ask($question, function (Answer $answer) {
				Log::newLogAnswer($this->bot, $answer);
				if ($answer->isInteractiveMessageReply()) {
					if ($answer->getValue() == 'start menu') {
                        $this->bot->startConversation(new MenuConversation());
					}
				} elseif($answer->getText() == '/setabouttext') {
                    $this->say(trans('messages.about myself'));
					$this->start();
				} else {
				    $this->start();
                }
			}
		);
	}

	public function aboutMyself()
	{
		$question = Question::create(trans('messages.about myself'))
			->addButton(Button::create(trans('buttons.menu'))->value('menu'));

		return $this->ask(
			$question,
			function (Answer $answer) {
				Log::newLogAnswer($this->bot, $answer);
				if ($answer->getValue() == 'menu') {
                    $this->bot->startConversation(new MenuConversation());
				}
			}
		);
	}
}