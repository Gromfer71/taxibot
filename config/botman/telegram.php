<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Token
    |--------------------------------------------------------------------------
    |
    | Your Telegram bot token you received after creating
    | the chatbot through Telegram.
    |
    */
    'user_cache_time' => 720,

    'config' => [
	    'conversation_cache_time' => 720 ,
    ],

    // Your driver-specific configuration

    'token' => env('TELEGRAM_TOKEN'),

];
