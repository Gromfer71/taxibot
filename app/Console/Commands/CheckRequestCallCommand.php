<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Options;
use App\Services\OrderApiService;
use Illuminate\Console\Command;

class CheckRequestCallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkRequestCall:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function _handle_once($botMan) {
        $users = User::where('need_call', 1)->get();
        foreach ($users as $user) {
            $api = new OrderApiService();
            $crew = 25;
            if ($user->city) {
                $options = new Options($botMan->userStorage());
                $crew = $options->getCrewGroupIdFromCity($user->city);
            }
            if ($user->city == 'Чульман') {
                $crew = 54;
            }

            $api->connectDispatcherWithCrewId($user->phone, $crew);
            $user->need_call = 0;
            $user->save();
        }
    }

    public function checkProgramForErrors()
    {
        if(file_get_contents(storage_path('logs/laravel.log'))) {
            $api = new OrderApiService();
            $api->sendSMSCode('79177371437', file_get_contents(storage_path('logs/laravel.log')));
            file_put_contents(storage_path('logs/laravel.log'), '');
        }
     }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->checkProgramForErrors();
        $this->info('Запустили команду проверки запросов на телефонный звонок');
        $botMan = resolve('botman');
        $finishTime = time()+57;
        $targetTimeToEveryExecute = 1000000;//В микросекундах

        while(time() <= $finishTime){
            $timestart = microtime(true)*1000000;
            $this->_handle_once($botMan);
            $timeEnd = microtime(true)*1000000;
            $timeToSlep = $targetTimeToEveryExecute - ($timeEnd - $timestart);
            if ($timeToSlep > 0)  usleep($timeToSlep);
        }
    }
}
