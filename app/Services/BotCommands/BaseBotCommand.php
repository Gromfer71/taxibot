<?php

namespace App\Services\BotCommands;

use App\Conversations\BaseConversation;
use App\Services\BotCommandFactory;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Базовый класс для команд бота. Команды наследуются от него. Можно обернуть в функционал, описанный здесь
 */
abstract class BaseBotCommand implements BotCommandInterface
{
    protected $bot;
    protected $conversation;

    /**
     * @param $bot
     * @param $conversation
     */
    public function __construct(BaseConversation $conversation)
    {
        $this->bot = $conversation->getBot();
        $this->conversation = $conversation;

    }

    /**
     * Выполнение команды
     *
     * @return void
     */
    public function execute()
    {
        Translator::setUp($this->conversation->getUser());
        // TODO: сюда можно добавить любое действие, выполняемое перед запуском команды: логгирование, проверки и тд и тп
    }
}