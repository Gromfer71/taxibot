<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        if(Config::getToken()) {
//            config(['botman.telegram.token' => Config::getToken()->value]);
//        }
//        if(Config::getTaxibotConfig()) {
//            config(['app.config_file' => Config::getTaxibotConfig()->value]);
//        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
