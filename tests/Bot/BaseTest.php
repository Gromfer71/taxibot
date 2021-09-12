<?php

namespace Tests\Bot;

use danog\MadelineProto\API;
use Exception;
use PHPUnit\Framework\Assert;

/**
 * Базовый класс для тестирования чат-бота через Madeline Proto.
 */
abstract class BaseTest
{
    const BOT_NAME = 'sk_taxi_test_bot';
    const SECONDS_FOR_BOT_RESPONSE = 3;

    static $bot_response_time;

    protected $botResponse;
    protected $proto;
    protected $errors;

    public function __construct(API $proto)
    {
        $this->proto = $proto;
        $this->errors = collect();
        static::$bot_response_time = microtime(true);
    }

    public function getErrors(): array
    {
        return $this->errors->filter(static function ($error) {
           // return array_get($error, 'error') !== 'УСПЕШНО';
            return $error;
        })->toArray();
    }

    public function mergeErrors($errors)
    {
        $this->errors = $this->errors->merge($errors);
    }

    protected function getBotResponse()
    {
        return $this->botResponse;
    }

    protected function assertEqualsWithLogging($first, $second)
    {
        try {
            Assert::assertEquals($first, $second);
            $error = 'УСПЕШНО';
        } catch (Exception $exception) {
            $error = $exception->getLine();
        }
        $this->addLog(['error' => $error, 'first' => $first, 'second' => $second]);
    }

    protected function addLog($params)
    {
        $this->errors->push(
            [
                'error' => $params['error'],
                'should be' => $params['first'],
                'was' => $params['second'],
            ]
        );
    }

    protected function restart()
    {
        $this->sendMessage('/restart');
        $this->waitForResponse();
    }

    protected function sendMessage($message)
    {
        $this->proto->messages->sendMessage(['peer' => '@sk_taxi_test_bot', 'message' => $message]);
        BaseTest::$bot_response_time = microtime(true);
        $this->setAndGetBotResponse();

    }

    protected function setAndGetBotResponse()
    {
        $this->waitForResponse();

        $this->botResponse = $this->getLastMessageFromHistory($this->getBotMessagesHistory());

        return $this->botResponse;
    }

    protected function waitForResponse()
    {
        sleep(self::SECONDS_FOR_BOT_RESPONSE);
    }

    protected function getLastMessageFromHistory($history)
    {
        return json_decode(json_encode($history, JSON_UNESCAPED_UNICODE), true)['messages'][0]['message'] ?? 'error';
    }

    protected function getBotMessagesHistory()
    {
        return $this->proto->messages->getHistory([
            /* Название канала, без @ */
            'peer' => self::BOT_NAME,
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 1,
            'max_id' => 9999999,
            'min_id' => 0,
        ]);
    }
}