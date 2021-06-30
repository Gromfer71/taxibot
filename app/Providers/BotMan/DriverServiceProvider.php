<?php

namespace App\Providers\BotMan;

use App\Models\Config;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use BotMan\Studio\Providers\DriverServiceProvider as ServiceProvider;
use VkBotMan\Drivers\VkDriver;

class DriverServiceProvider extends ServiceProvider
{
    /**
     * The drivers that should be loaded to
     * use with BotMan
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();

        foreach ($this->drivers as $driver) {
            DriverManager::loadDriver($driver);
        }
    }
}
