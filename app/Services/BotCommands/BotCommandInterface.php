<?php

namespace App\Services\BotCommands;

use App\Conversations\BaseConversation;

/**
 * Интерфейс для создания действий бота, которые могут повторяться, дублироваться и т.д.
 */
interface BotCommandInterface
{
    /**
     * Передаем только диалог в котором уже есть бот
     *
     * @param \App\Conversations\BaseConversation $conversation
     */
    public function __construct(BaseConversation $conversation);


    /**
     * Выполнение команды
     *
     * @return mixed
     */
    public function execute();

    /**
     * @return mixed
     */
    public function getAnswer();

    /**
     * @param mixed $answer
     */
    public function setAnswer($answer): void;

}