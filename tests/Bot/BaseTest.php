<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace Tests\Bot;

use danog\MadelineProto\API;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

/**
 * Базовый класс для тестирования чат-бота через Madeline Proto.
 */
abstract class BaseTest extends Assert
{
    private const BOT_NAME = 'sk_taxi_test_bot';
    private const SECONDS_FOR_BOT_RESPONSE = 3;

    protected string $botResponse;
    protected API $proto;
    protected Collection $testResults;

    /**
     * @param \danog\MadelineProto\API $proto
     */
    public function __construct(API $proto)
    {
        $this->proto = $proto;
        $this->testResults = collect();
    }

    /**
     * Возвращает массив с результатами теста по фильтру
     *
     * @return array
     */
    public function getTestResults(): array
    {
        return $this->testResults->filter(static function ($error) {
             return array_get($error, 'error') !== 'УСПЕШНО';
        })->toArray();
    }

    /**
     * Записывает последний ответ бота в поле
     *
     * @return string
     */
    public function createResponse(): string
    {
        $this->botResponse = $this->getLastMessage();

        return $this->botResponse;
    }

    /**
     * Отправляет сообщение боту
     *
     * @param string $message Текст сообщения
     */
    protected function sendMessage(string $message): void
    {
        $this->proto->messages->sendMessage(['peer' => '' . self::BOT_NAME, 'message' => $message]);
        $this->waitForResponse();
        $this->createResponse();
    }

    /**
     * После завершения тестов одним классом, соединяет результаты с предыдущими
     *
     * @param $errors
     */
    protected function mergeErrors($errors): void
    {
        $this->testResults = $this->testResults->merge($errors);
    }

    /**
     * Ждет после отправки сообщения ответа от бота
     */
    private function waitForResponse(): void
    {
        sleep(self::SECONDS_FOR_BOT_RESPONSE);
    }

    /**
     * Делает рестарт бота, запуская команду рестарта
     */
    protected function restart(): void
    {
        $this->sendMessage('/restart');
        $this->waitForResponse();
    }

    /**
     * Возвращает последнее сообщение, отправленное ботом
     *
     * @return mixed
     */
    private function getLastMessage()
    {
        /** @noinspection PhpParamsInspection */
        return array_get(
            array_get(array_get($this->proto->messages->getHistory($this->getHistoryParams()), 'messages'), '0'),
            'message'
        );
    }

    /**
     * Возвращает коллекцию кнопок с их текстами
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    private function getButtons()
    {
        $reply = $this->proto->messages->getHistory($this->getHistoryParams());
        $buttons = [];
        foreach ($reply['messages'][0]['reply_markup']['rows'] as $row) {
            foreach ($row['buttons'] as $button) {
                $buttons[] = $button['text'];
            }
        }

        return collect($buttons);
    }

    /**
     * Возвращает последнее (!) сообщение в диалоге
     *
     * @return string
     */
    protected function getBotResponse(): string
    {
        return $this->botResponse;
    }

    /**
     * Параметры для получения истории сообщений
     */
    private function getHistoryParams(): array
    {
        return [
            'peer' => self::BOT_NAME,
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 1,
            'max_id' => 9999999,
            'min_id' => 0,
        ];
    }
}