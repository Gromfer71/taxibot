<?php
namespace App\Conversations;


use App\Models\AddressHistory;
use App\Models\Config;
use App\Models\Log;
use App\Models\User;
use App\Services\Address;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\App;

abstract class BaseConversation extends Conversation
{
    public function checkConfig(){
        if (!$this->bot->channelStorage()->get('options')) {
            $this->_sayDebug('Конфига нет');
            $this->_loadConfig();
        }
        if (!$this->bot->channelStorage()->get('optionsDate')) {
            $this->_sayDebug('Даты конфига нет');
            $this->_loadConfig();
        }
        $optionsDate = $this->bot->channelStorage()->get('optionsDate');
        if ($optionsDate < (time()-env('CONFIG_CACHE_TIME_IN_HOUR',12))){
            $this->_sayDebug('Срок действия конфига истек');
            $this->_loadConfig();
        }
    }

    public function _loadConfig(){
        $this->_sayDebug('Стартуем загрузку конфига');
        $this->bot->channelStorage()->save(['options' => file_get_contents('https://sk-taxi.ru/tmfront/config.json'),'optionsDate' => time()]);
        $this->_sayDebug('Конфиг загружен');
    }


    public function _sayDebug($message)
    {
        if (config('app.debug')) {
            $this->say(
                '||DEBUG|| '.$message,
                $this->bot->getUser()->getId()
            );
        }
    }

    public function _fallback($answer){
        if($answer->getValue() == 'aborted order' || $answer->getValue() == 'finished order') {
            $this->bot->startConversation(new \App\Conversations\StartConversation());
            return;
        }

        $className = get_class($this);
        $this->_sayDebug('Ошибка - потерянный диалог в диалоге - '.$className.' text - '.$answer->getText().' value - '.$answer->getValue());
        $this->_sayDebug('Возвращаемся к диалогу '.$className);
        $this->bot->startConversation(new $className);
    }

    public function _filterChangePrice($prices,$key_price = 'changed_price'){
        if($this->bot->userStorage()->get($key_price)) {
            $changedPriceId = $this->bot->userStorage()->get($key_price)['id'];
            foreach ($prices as $key => $price){
                if ($price->id == $changedPriceId) unset($prices[$key]);
            }
        }
        return $prices;
    }

    public function _addChangePriceDefaultButtons($question){
        $question->addButton(Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::CHANGE_PRICE_MENU_FORMAT]));
        $question->addButton(Button::create(trans('buttons.cancel change price')));
        return $question;
    }
    public function end()
    {
        $question = Question::create(trans('messages.thx for order'), $this->bot->getUser()->getId())->addButtons([
            Button::create('Продолжить')->value('Продолжить'),
        ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            $this->bot->startConversation(new MenuConversation());
        });
    }

    public function getUser()
    {
        $user = User::find($this->bot->getUser()->getId());
        if($user) {
            return $user;
        } else {
            throw new \Exception('null user exception');
        }
    }
}