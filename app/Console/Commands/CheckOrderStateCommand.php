<?php

namespace App\Console\Commands;

use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\MessageGeneratorService;
use App\Services\OrderApiService;
use App\Services\OrderService;
use App\Services\Translator;
use App\Traits\BotManagerTrait;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Console\Command;


class CheckOrderStateCommand extends Command
{
    use BotManagerTrait;

    public const TELEGRAM_DRIVER_NAME = 'Telegram';
    public const VK_DRIVER_NAME = 'VkCommunityCallback';
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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Запустили команду проверки статусов заказов');

        $finishTime = time() + 57;
        $targetTimeToEveryExecute = 1000000;//В микросекундах

        $botMan = resolve('botman');

        while (time() <= $finishTime) {
            $timestart = microtime(true) * 1000000;
            $this->_handle_once($botMan);
            $timeEnd = microtime(true) * 1000000;
            $timeToSlep = $targetTimeToEveryExecute - ($timeEnd - $timestart);
            if ($timeToSlep > 0) {
                usleep($timeToSlep);
            }
        }
    }

    private function _handle_once($botMan)
    {
        $actualOrders = OrderHistory::getAllActualOrders();
        foreach ($actualOrders as $actualOrder) {
            // если пользователя сбросили через админку, отменяем его заказ
            if ($actualOrder->user->should_reset) {
                $actualOrder->cancelOrder();
                continue;
            }

            // получение старого и нового состояния заказа
            $oldState = $actualOrder->getCurrentOrderState();
            $oldStateId = $oldState->state_id ?? OrderHistory::NEW_ORDER;

            $newState = (new OrderApiService())->getOrderState($actualOrder);
            $newStateId = $actualOrder->checkOrder($newState);

            $newState = $newState->data;
            $actualOrder->refresh();
            if ($actualOrder->relevance != 0) {
                continue;
            }

            // инициация словаря
            $user = User::where('id', $actualOrder->user_id)->first();
            if (is_null($user->lang_id)) {
                $user->setDefaultLang();
            }
            $package = LangPackage::find($user->lang_id);
            Translator::$lang = $package->code;

            // получением платформу на который был произведен заказ
            if ($actualOrder->platform == self::TELEGRAM_DRIVER_NAME) {
                $driverName = TelegramDriver::class;
                $recipientId = $user->telegram_id;
            } elseif ($actualOrder->platform == self::VK_DRIVER_NAME) {
                $driverName = VkCommunityCallbackDriver::class;
                $recipientId = $user->vk_id;
            } else {
                //временно оставим по дефолту телеграм, чтобы драйвер не был null
                $driverName = TelegramDriver::class;
                $recipientId = $user->telegram_id;
            }

            if ($newState && $oldState) {
                if (Address::isAddressChangedFromState($oldState, $newState)) {
                    $storage = $botMan->userStorageFromId(
                        User::where('id', $actualOrder->user_id)->first()->telegram_id
                    );

                    // newState - это когда меняет диспетчер, т.е. адреса ставим новые, а для отладки когда меняем адреса в бд, надо юзать oldState

                    Address::updateAddressesInStorage($newState, $storage);

                    $orderService = new OrderService($storage);
                    $orderService->calcPrice();


                    $botMan->say(Translator::trans('messages.order state changed'), $recipientId, $driverName);
                    $botMan->say(MessageGeneratorService::getFullOrderInfoFromStorage($storage), $recipientId, $driverName);
                }
            }


            // если статус заказа поменялся, только тогда производим какие-то действия
            {
                if (!$newStateId) {
                    continue;
                }
            }


            if (config('app.debug')) {
                $botMan->say(
                    '||DEBUG|| Новый статус заказа - ' . $newStateId . ' <-- ' . $oldStateId . ' ',
                    $recipientId,
                    $driverName
                );
            }

            // водитель взял наш заказ
            if ($newStateId == OrderHistory::DRIVER_ASSIGNED) {
                $api = new OrderApiService();
                $time = $api->driverTimeCount($actualOrder->id)->data->DRIVER_TIMECOUNT;
                if ($time == 0) {
                    continue;
                }
                $auto = $actualOrder->getAutoInfo();
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.auto info with time', ['time' => $time, 'auto' => $auto]),
                    [ButtonsStructure::CANCEL_ORDER, ButtonsStructure::ORDER_CONFIRM],
                    ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
                );
                $botMan->say($question, $recipientId, $driverName);
                // водитель взял в очередь заказ
            } elseif ($newStateId == OrderHistory::IN_QUEUE) {
                $auto = $actualOrder->getAutoInfo();
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.auto info without time', ['auto' => $auto]),
                    [ButtonsStructure::CANCEL_ORDER]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($actualOrder->asAbortedFromQueue()) {
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.queue aborted by driver'),
                    [ButtonsStructure::CANCEL_ORDER]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::DRIVER_ABORTED_FROM_ORDER) {
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.driver aborted from order'),
                    [ButtonsStructure::CANCEL_ORDER]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::CAR_AT_PLACE) {
                if ($oldStateId == OrderHistory::REQUEST_FOR_ABORT_BY_DRIVER) {
                    continue;
                }
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans(
                        'messages.auto waits for client',
                        ['auto' => $actualOrder->getAutoInfo()]
                    ),
                    [ButtonsStructure::CANCEL_ORDER, ButtonsStructure::CLIENT_GOES_OUT],
                    ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::CLIENT_INSIDE) {
                $question = ComplexQuestion::createWithSimpleButtons(
                    '👍',
                    [
                        ButtonsStructure::FINISH_ORDER,
                        ButtonsStructure::NEED_DISPATCHER,
                        ButtonsStructure::NEED_DRIVER,
                        ButtonsStructure::GET_DRIVER_LOCATION
                    ],
                    ['config' => ButtonsFormatterService::ONE_TWO_DIALOG_MENU_FORMAT]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::ABORTED || $newStateId == OrderHistory::ABORTED_BY_DRIVER) {
                $actualOrder->setAbortedOrder();
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.aborted order'),
                    [ButtonsStructure::ABORTED_ORDER]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::FINISHED_BY_DRIVER) {
                $actualOrder->finishOrder();
                $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.thx for order'));
                if ($actualOrder->platform == self::TELEGRAM_DRIVER_NAME) {
                    $storage = $botMan->userStorageFromId(
                        User::where('id', $actualOrder->user_id)->first()->telegram_id
                    );
                } elseif ($actualOrder->platform == self::VK_DRIVER_NAME) {
                    $storage = $botMan->userStorageFromId(User::where('id', $actualOrder->user_id)->first()->vk_id);
                }
                if (!$storage->get(
                        'second_address_will_say_to_driver_flag'
                    ) && !$storage->get('is_route_from_favorite')) {
                    $question = ComplexQuestion::setButtons($question, [ButtonsStructure::ADD_TO_FAVORITE_ROUTES]);
                }
                $question = ComplexQuestion::setButtons($question, [ButtonsStructure::EXIT_TO_MENU]);
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::CLIENT_DONT_COME_OUT || $newStateId == OrderHistory::CLIENT_DONT_COME_OUT_2) {
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.dont come out'),
                    [ButtonsStructure::CLIENT_GOES_OUT_LATE, ButtonsStructure::CANCEL_ORDER],
                    ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
                );
                $botMan->say($question, $recipientId, $driverName);
            } elseif ($newStateId == OrderHistory::ORDER_NOT_FOUND) {
                //Заказ удален
                $actualOrder->refresh();
                $actualOrder->setDeletedOrder();
                $question = ComplexQuestion::createWithSimpleButtons(
                    Translator::trans('messages.aborted order'),
                    [ButtonsStructure::ABORTED_ORDER]
                );
                $botMan->say($question, $recipientId, $driverName);
            }
        }
    }
}
