<?php

namespace App\Http\Controllers;

use App\Conversations\ExampleConversation;
use App\Conversations\StartConversation;
use App\Models\OrderHistory;
use App\Services\OrderApiService;
use BotMan\BotMan\BotMan;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {

        $api = new OrderApiService();
        print_r(OrderHistory::getActualOrder(1585139223)->crew_id);
        dd($api->getOrderState(OrderHistory::getActualOrder(1585139223)));
        $driverLocation = $api->getCrewCoords($api->getOrderState(OrderHistory::getActualOrder(1585139223))->crew_id);
        print_r($driverLocation);
        if($driverLocation) {
            OrderApiService::sendDriverLocation(1585139223, $driverLocation->lat, $driverLocation->lon);
        } else {
            print_r('error');
        }

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
}
