<?php

namespace App\Console\Commands;

use App\Models\OrderHistory;
use App\Models\User;
use App\Services\ButtonsFormatterService;
use App\Services\OrderApiService;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CheckOrderStateCommand extends Command
{
    const TELEGRAM_DRIVER_NAME = 'Telegram';
    const VK_DRIVER_NAME = 'VkCommunityCallback';
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'CheckOrderStateCommand:execute';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	private function _handle_once($botMan){


        $actualOrders = OrderHistory::getAllActualOrders();
        foreach ($actualOrders as $actualOrder) {
            $oldStateId = $actualOrder->getCurrentOrderState()->state_id ?? OrderHistory::NEW_ORDER;
            $newStateId = $actualOrder->checkOrder();
            $actualOrder->refresh();
            if ($actualOrder->relevance != 0) continue;

            $user = User::find($actualOrder->user_id);
            if($actualOrder->platform == self::TELEGRAM_DRIVER_NAME) {
                $driverName = TelegramDriver::class;
                $recipientId = $user->telegram_id;
            }elseif($actualOrder->platform == self::VK_DRIVER_NAME) {
                $driverName = VkCommunityCallbackDriver::class;
                $recipientId = $user->vk_id;
            } else {
                //временно оставим по дефолту телеграм, чтобы драйвер не был null
                $driverName = TelegramDriver::class;
                $recipientId = $user->telegram_id;
            }







            if ($newStateId) {
                if (config('app.debug')) {
                    $botMan->say('||DEBUG|| Новый статус заказа - ' . $newStateId.' <-- '.$oldStateId.' ', $recipientId, $driverName);
                }
                if ($newStateId == OrderHistory::DRIVER_ASSIGNED) {
                    $api = new OrderApiService();
                    $time = $api->driverTimeCount($actualOrder->id)->data->DRIVER_TIMECOUNT;
                    if ($time == 0) continue;
                    $auto = $actualOrder->getAutoInfo() ?? '';
                    $question = Question::create(trans('messages.auto info with time', ['time' => $time, 'auto' => $auto]),
                        $recipientId)->addButtons([
                        Button::create(trans('buttons.order_cancel'))->additionalParameters(['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]),
                        Button::create(trans('buttons.order_confirm'))
                    ]);

                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                } elseif ($newStateId == OrderHistory::IN_QUEUE) {
                    $auto = $actualOrder->getAutoInfo() ?? '';
                    $question = Question::create(trans('messages.auto info without time', ['auto' => $auto]),
                        $recipientId)->addButtons([
                        Button::create(trans('buttons.order_cancel')),
                    ]);

                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                } elseif ($actualOrder->asAbortedFromQueue()) {
                    $question = Question::create(trans('messages.queue aborted by driver'),
                        $recipientId)->addButtons([
                        Button::create(trans('buttons.order_cancel'))
                    ]);
                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                } elseif ($newStateId == OrderHistory::DRIVER_ABORTED_FROM_ORDER) {
                    $question = Question::create(trans('messages.driver aborted from order'),
                        $recipientId)->addButtons([
                        Button::create(trans('buttons.order_cancel'))
                    ]);
                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                } elseif ($newStateId == OrderHistory::CAR_AT_PLACE) {

                    if ($oldStateId == OrderHistory::REQUEST_FOR_ABORT_BY_DRIVER) return;
                    $question = Question::create(trans('messages.auto waits for client', ['auto' => $actualOrder->getAutoInfo()]),
                        $recipientId)->addButtons([
                        Button::create(trans('buttons.cancel order'))->additionalParameters(['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]),
                        Button::create(trans('buttons.client_goes_out')),
                    ]);

                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                } elseif ( $newStateId == OrderHistory::CLIENT_INSIDE) {
                    $question = Question::create('👍', $recipientId)->addButtons([
                        Button::create(trans('buttons.finish order'))->additionalParameters(['config' => ButtonsFormatterService::ONE_TWO_DIALOG_MENU_FORMAT]),
                        Button::create(trans('buttons.need dispatcher')),
                        Button::create(trans('buttons.need driver')),
                    ]);
                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                } elseif ( $newStateId == OrderHistory::ABORTED ||$newStateId == OrderHistory::ABORTED_BY_DRIVER) {
                    $actualOrder->setAbortedOrder();
                    $question = Question::create(trans('messages.aborted order'), $recipientId)->addButtons([
                        Button::create(trans('buttons.aborted order')),
                    ]);
                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
            } elseif ($newStateId == OrderHistory::FINISHED_BY_DRIVER) {
                $actualOrder->finishOrder();
                $question = Question::create(trans('messages.thx for order'), $recipientId)->addButtons([
                    Button::create(trans('buttons.finished order')),
                ]);
                $botMan->say($question, $recipientId, $driverName);
                $botMan->listen();
            }elseif ( $newStateId == OrderHistory::CLIENT_DONT_COME_OUT || $newStateId == OrderHistory::CLIENT_DONT_COME_OUT_2) {
                   $question = Question::create(trans('messages.dont come out'), $recipientId)->addButtons([
                        Button::create(trans('buttons.client_goes_out_late'))->additionalParameters(['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]),
                        Button::create(trans('buttons.cancel order')),
                    ]);
                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
             }elseif ( $newStateId == OrderHistory::ORDER_NOT_FOUND) {
                    //Заказ удален
                    $actualOrder->refresh();
                    if ($actualOrder->relevance != 0) continue;
                    $actualOrder->setDeletedOrder();
                    $question = Question::create(trans('messages.aborted order'), $recipientId)->addButtons([
                        Button::create(trans('buttons.aborted order')),
                    ]);
                    $botMan->say($question, $recipientId, $driverName);
                    $botMan->listen();
                }

            }
        }


    }

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
        $this->info('Запустили команду');

        $finishTime = time()+57;
        $targetTimeToEveryExecute = 1000000;//В микросекундах



        $botMan = resolve('botman');

        while(time() <= $finishTime){
            $timestart = microtime(true)*1000000;
            $this->_handle_once($botMan);
            $timeEnd = microtime(true)*1000000;
            $timeToSlep = $targetTimeToEveryExecute - ($timeEnd - $timestart);
            if ($timeToSlep > 0)  usleep($timeToSlep);
        }


      }
}
