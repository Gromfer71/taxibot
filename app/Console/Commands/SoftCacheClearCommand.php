<?php

namespace App\Console\Commands;

use App\Models\OrderHistory;
use Illuminate\Console\Command;

class SoftCacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'softCacheClear';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Запустили команду мягкой очистки кеша');
        $maxTime = time()+600;

        while(OrderHistory::whereNotNull('relevance')->get()->isNotEmpty() && time() <= $maxTime){
            sleep(5);
        }

        $activeOrders = OrderHistory::whereNotNull('relevance')->get();
        if($activeOrders->isEmpty()) {
            \Artisan::call('cache:clear');
        }
    }
}
