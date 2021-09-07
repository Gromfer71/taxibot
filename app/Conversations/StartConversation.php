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

	    if($this->bot->userStorage()->get('error')) {
	        $this->say($this->__('messages.program error message'));
	        $this->bot->userStorage()->delete('error');
        }

	    if(!$this->bot->getUser() || !$this->bot->getUser()->getId()) {
	        return;
        }
		$user = User::find($this->bot->getUser()->getId());
		if (! $user) {
			$this->start();
		} elseif (!OrderHistory::getActualOrder($user->id, $this->bot->getDriver()->getName())) {
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
		    $user->setPlatformId($this->bot);

			$user->save();
		}

		$this->checkConfig();

		$question = Question::create($this->__('messages.welcome message'), $this->bot->getUser()->getId())
			->addButtons(
				[
					Button::create($this->__('buttons.start menu'))->value('start menu'),
				]
			);

		return $this->ask($question, function (Answer $answer) {
				Log::newLogAnswer($this->bot, $answer);
				if ($answer->isInteractiveMessageReply()) {
					if ($answer->getValue() == 'start menu') {
                        $this->bot->startConversation(new MenuConversation());
					}
				} elseif($answer->getText() == '/setabouttext') {
                    $this->say($this->__('messages.about myself'));
					$this->start();
				} else {
				    $this->start();
                }
			}
		);
	}

	public function aboutMyself()
	{
		$question = Question::create($this->__('messages.about myself'))
			->addButton(Button::create($this->__('buttons.menu'))->value('menu'));

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