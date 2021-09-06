<?php
namespace Tests\BotMan;

use danog\MadelineProto\API;
use PHPUnit\Framework\Assert;

abstract class BaseTest extends Assert
{
    const SECONDS_FOR_BOT_RESPONSE = 3;

    protected $proto;
    protected $errors;


    public function getErrors()
    {
        return $this->errors;
    }

    public function __construct(API $proto)
    {
        $this->proto = $proto;
        $this->errors = collect();
    }


    protected function waitForResponse()
    {
        sleep(self::SECONDS_FOR_BOT_RESPONSE);
    }

    protected function getBotMessagesHistory()
    {
        return $this->proto->messages->getHistory([
            /* Название канала, без @ */
            'peer' => 'sk_taxi_test_bot',
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 1,
            'max_id' => 9999999,
            'min_id' => 0,
        ]);
    }

    protected function getLastMessageFromHistory($history)
    {
        return json_decode(json_encode($history, JSON_UNESCAPED_UNICODE), true)['messages'][0]['message'] ?? 'error';
    }


    protected function getBotResponse()
    {
        $this->waitForResponse();

        return $this->getLastMessageFromHistory($this->getBotMessagesHistory());
    }

    protected function sendMessage($message)
    {
        $this->proto->messages->sendMessage(['peer' => '@sk_taxi_test_bot', 'message' => $message]);
    }

    protected function assertEqualsWithLogging($first, $second)
    {
        try {
            $this->assertEquals($first, $second);
        } catch (\Exception $exception) {
            $this->errors->push(['error' => $exception->getMessage(), 'должно быть' => $first, 'было' => $second]);
        }
    }

    protected function restart()
    {
        $this->sendMessage('/restart');
        $this->waitForResponse();
    }

    public function mergeErrors($errors)
    {
        $this->errors = $this->errors->merge($errors);
    }
}