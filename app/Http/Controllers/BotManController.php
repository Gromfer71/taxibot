<?php

namespace App\Http\Controllers;

use App\Conversations\StartConversation;
use BotMan\BotMan\BotMan;

/**
 * Контроллер управления ботом. Содержит главную точку входа чат-бота
 */
class BotManController extends Controller
{

    /**
     * Точка входа чат-бота. Сюда установлен webhook телеграм и настраивается api вк
     */
    public function handle()
    {
        $bot = app('botman');
        $bot->listen();
    }

    /**
     * Обработка любого сообщения начинается с запуска стартового диалога
     *
     * @param BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new StartConversation());
    }
}
