<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings\Database\Memory;
use Illuminate\Console\Command;
use Tests\Bot\MainMenu\MainMenuTest;

class RunTestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        require './tests/Bot/Madeline/madeline.php';
        $settings = new \danog\MadelineProto\Settings\Database\Memory();
        $MadelineProto = new API('session.madeline', $settings);
        $MadelineProto->updateSettings($settings);
        $MadelineProto->start();
        $MadelineProto->async(false);
        $test = new MainMenuTest($MadelineProto);
        $test->run();
    }
}
