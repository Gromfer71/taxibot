<?php

namespace App\Services\BotCommands;

use App\Conversations\BaseConversation;
use App\Services\Translator;

/**
 * Базовый класс для команд бота. Команды наследуются от него. Можно обернуть в функционал, описанный здесь
 */
abstract class BaseBotCommand implements BotCommandInterface
{
    protected $bot;
    protected $conversation;
    protected $answer;

    /**
     * @param \App\Conversations\BaseConversation $conversation
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
        // TODO: сюда можно добавить любое действие, выполняемое перед запуском команды: логирование, проверки и тд и тп
    }

    /**
     * @return mixed
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param mixed $answer
     */
    public function setAnswer($answer): void
    {
        $this->answer = $answer;
    }
}