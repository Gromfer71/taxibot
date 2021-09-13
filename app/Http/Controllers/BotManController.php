<?php

namespace App\Http\Controllers;

use App\Conversations\ExampleConversation;
use App\Conversations\StartConversation;
use App\Models\Config;
use App\Models\OrderHistory;
use App\Services\OrderApiService;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use danog\MadelineProto\API;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Tests\BotMan\MainMenu\MainMenuTest;


class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->listen();
    }

    /**
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }


    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new StartConversation());
    }

    public function executeTests()
    {

        require '../app/Madeline/madeline.php';
        $settings = new \danog\MadelineProto\Settings\Database\Memory;
        $MadelineProto = new API('session.madeline', $settings);
        $MadelineProto->updateSettings($settings);
        $MadelineProto->start();
        $MadelineProto->async(false);
        $test = new MainMenuTest($MadelineProto);
        $test->run();

        return view('tests.result', ['data' => $test->getErrors()]);

    }
}
