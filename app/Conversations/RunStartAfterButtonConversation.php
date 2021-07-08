<?php

namespace App\Conversations;

use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class RunStartAfterButtonConversation extends BaseConversation
{
    public function run()
    {
       $this->start();
    }
    public function start(){
        $this->_sayDebug('RunStartAfterButtonConversation');
        $message = trans('messages.cancel order');
        $question = Question::create($message, $this->bot->getUser()->getId())
            ->addButtons(
                [
                    Button::create(trans('buttons.continue'))->value('continue')
                ]
            );
        $this->_sayDebug('RunStartAfterButtonConversation - end');
        return $this->ask($question, function (Answer $answer) {
            $this->_sayDebug('RunStartAfterButtonConversation - answer');
            Log::newLogAnswer($this->bot, $answer);
            $this->bot->startConversation(new StartConversation());
        });
    }



   }