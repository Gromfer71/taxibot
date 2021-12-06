<?php


namespace App\Services;


use App\Models\Config;
use App\Models\LogApi;
use App\Models\OrderHistory;
use App\Models\User;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Storages\Storage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Exception;
use Illuminate\Support\Carbon;

class OrderApiService
{

    public const ORDER_CONFIRMED_BY_USER = 88;
    public const USER_GOES_OUT = 50;
    public const ORDER_FROM_VK_OPTION = 150;
    public const ORDER_FROM_TELEGRAM_OPTION = 149;

    public static function replacePhoneCountyCode($phone)
    {
        if (substr($phone, 0, 1) == '7') {
            $phone = substr($phone, 1);
        } elseif (substr($phone, 0, 2) == '+7') {
            $phone = substr($phone, 2);
        } elseif (substr($phone, 0, 1) == '8') {
            $phone = substr($phone, 1);
        }

        return $phone;
    }

    /**
     * @param BotMan $bot
     * @param double $lat
     * @param double $lon
     */
    public static function sendDriverLocation(BotMan $bot, $lat, $lon)
    {
        if (get_class($bot->getDriver()) == VkCommunityCallbackDriver::class) {
            $data['lat'] = $lat;
            $data['long'] = $lon;
            $data['message'] = 'Геолокация водителя:';
            $data['random_id'] = 0;
            $data['user_ids'] = $bot->getUser()->getId();
            $bot->getDriver()->api('messages.send', $data);
        } elseif (get_class($bot->getDriver()) == TelegramDriver::class) {
            $query = http_build_query(['chat_id' => $bot->getUser()->getId(), 'latitude' => $lat, 'longitude' => $lon]);
            file_get_contents('https://api.telegram.org/bot' . Config::getToken()->value . '/sendlocation?' . $query);
        }
    }

    public static function getOptionIdFromDriverName($driverName)
    {
        if ($driverName == VkCommunityCallbackDriver::DRIVER_NAME) {
            return self::ORDER_FROM_VK_OPTION;
        } elseif ($driverName == TelegramDriver::DRIVER_NAME) {
            return self::ORDER_FROM_TELEGRAM_OPTION;
        }
    }

    public function createOrder(BotMan $bot, $useBonus = false)
    {
        $bot->userStorage()->save(['platform_option' => self::getOptionIdFromDriverName($bot->getDriver()->getName())]);
        $user = User::find($bot->getUser()->getId());
        if (!$user) {
            return null;
        }
        $addresses = (array)$this->analyzeRoute($bot->userStorage())->data->addresses;
        $oldAddresses = collect($bot->userStorage()->get('address'))->values()->all();
        foreach ($addresses as $key => $address) {
            $addresses[$key]->address = $oldAddresses[$key];
            $addresses[$key]->lat = (double)$addresses[$key]->lat;
            $addresses[$key]->lon = (double)$addresses[$key]->lon;
        }

        $options = new Options();
        if (config('app.debug')) {
            $phone = '89999999999';
        } else {
            $phone = '8' . $user->phone;
        }


        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'addresses' => $addresses,
                                         'comment' => $bot->userStorage()->get('comment'),
                                         'crew_group_id' => $bot->userStorage()->get('crew_group_id'),
                                         'is_prior' => false,
                                         'order_params' => $options->getOrderParamsArray($bot),
                                         'phone' => $phone,
                                         'server_time_offset' => 0,
                                         'source_time' => Carbon::createFromTimestamp(time())->format('YmdHis'),
                                         'tariff_id' => $bot->userStorage()->get('tariff_id'),
                                         'use_bonus' => $useBonus,
                                     ]),
        ];


        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fcreate_order2',
            $params
        );

        return json_decode($response, true);
    }

    public function cancelOrder($order)
    {
        $this->changeOrderState($order, OrderHistory::ABORTED_BY_DRIVER);
    }

    public function finishOrder($order)
    {
        $this->changeOrderState($order, OrderHistory::FINISHED);
    }

    public function getCurrentOrders(User $user)
    {
        if (config('app.debug')) {
            $client_id = 275190;
        } else {
            $client_id = $user->server_id;
        }
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'client_id' => $client_id,
                                     ]),
        ];
        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fget_current_orders',
            $params
        );

        return json_decode($response);
    }

    public function getOrderState($order)
    {
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'order_id' => $order->id,
                                     ]),
        ];

        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fget_order_state',
            $params
        );

        return json_decode($response);
    }

    public function changeOrderState($order, $state)
    {
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'order_id' => $order->id,
                                         'new_state' => $state,
                                     ]),
        ];
        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fchange_order_state',
            $params
        );

        return json_decode($response);
    }

    public function changeOrderPrice(BotMan $bot, $order)
    {
        $options = new Options($bot->userStorage());
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'order_id' => $order->id,
                                         'auto_recalc_cost' => true,
                                         'order_params' => $options->getOrderParamsArray($bot)
                                     ]),
        ];


        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fupdate_order',
            $params
        );
        $this->driverTimeCount($order->id);
        $this->getOrderState($order);

        return json_decode($response);
    }

    public function connectClientAndDriver($order)
    {
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'order_id' => $order->id,
                                     ]),
        ];

        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Ftm_tapi%2F1.0%2Fconnect_client_and_driver',
            $params
        );

        return json_decode($response);
    }

    public function connectDispatcherWithCrewId($phone, $crew)
    {
        $endpoint = 'https://sk-taxi.ru/tmapi/call-dispatcher-with-crew-id.php';

        $postData = json_encode(
            [
                'phone' => '8' . $phone,
                'crew' => $crew,
            ]
        );
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData,
        ];

        $response = $this->file_get_contents_with_logging($endpoint, $params);

        return json_decode($response);
    }

    public function selectTariffForOrder($crewGroupId, $lat, $lon)
    {
        $addresses = [];
        if (is_array($lat)) {
            foreach ($lat as $key => $item) {
                $addresses[] = (object)['lat' => (float)$lat[$key], 'lon' => (float)$lat[$key]];
            }
        }

        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                                         'crew_group_id' => $crewGroupId,
                                         'addresses' => $addresses,
                                     ])
        ];

        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fselect_tariff_for_order',
            $params
        );


        return json_decode($response);
    }

    public function calcOrderPrice($tariffId, $orderParams, Storage $storage)
    {
        $data = $this->analyzeRoute($storage)->data;

        $addresses = collect((array)$data->addresses);

        $stops = collect((array)$data->addresses)->except([0, $addresses->count() - 1]);
        foreach ($stops as $key => $stop) {
            $stops[$key] = (array)$stops[$key];
        }

        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(
                [
                    'tariff_id' => $tariffId,
                    'order_params' => array_values($orderParams),
                    'dest_lat' => $addresses->last()->lat,
                    'dest_lon' => $addresses->last()->lon,
                    'src_lat' => $addresses->first()->lat,
                    'src_lon' => $addresses->first()->lon,
                    'distance_city' => $data->city_dist,
                    'distance_country' => $data->country_dist,
                    'dest_zone_id' => $addresses->last()->zone_id,
                    'source_zone_id' => $addresses->first()->zone_id,
                    'stops' => array_values($stops->toArray()),
                ]
            ),
        ];

        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fcalc_order_cost2',
            $params
        );
        return json_decode($response);
    }

    public function analyzeRoute(Storage $storage)
    {
        $lat = collect($storage->get('lat'))->values()->all();
        $lon = collect($storage->get('lon'))->values()->all();
        $oldAddresses = collect($storage->get('address'))->values()->all();
        $addresses = collect();
        foreach ($oldAddresses as $key => $address) {
            $addresses->push(
                [
                    'address' => $address,
                    'lat' => (float)$lat[$key],
                    'lon' => (float)$lon[$key],
                ]
            );
        }
        $addresses = $addresses->toArray();


        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(
                [
                    'get_full_route_coords' => false,
                    'addresses' => $addresses,
                ]
            ),
        ];

        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=/common_api/1.0/analyze_route2',
            $params
        );


        return json_decode($response);
    }

    public function sendSMSCode($phone, $code)
    {
        if (substr($phone, 0, 1) == '8') {
            $phone = substr($phone, 1);
            $phone = '7' . $phone;
        } elseif (substr($phone, 0, 2) == '+7') {
            $phone = substr($phone, 1);
        }

        return file_get_contents(
            'https://sk-taxi.ru/tmapi/sms.php?key=' . env(
                'SMS_KEY',
                'FN47XhzkAhS4jnbdyt5D'
            ) . '&to=' . $phone . '&message=' . urlencode(env('SMS_TEXT', 'Ваш код подтверждения:') . ' ' . $code),
            false
        );
    }

    public function callSMSCode($phone, $code)
    {
        if (substr($phone, 0, 1) == '8') {
            $phone = substr($phone, 1);
            $phone = '7' . $phone;
        } elseif (substr($phone, 0, 2) == '+7') {
            $phone = substr($phone, 1);
        }

        return file_get_contents(
            'https://sk-taxi.ru/tmapi/call.php?key=' . env(
                'SMS_KEY',
                'FN47XhzkAhS4jnbdyt5D'
            ) . '&to=' . $phone . '&message=' . $code,
            false
        );
    }

    public function getRandomSMSCode()
    {
        $haystack = '0123456789';
        return substr(str_shuffle($haystack), 0, 4);
    }

    public function driverTimeCount($orderId)
    {
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(
                [
                    'FIELDS' => 'DISCOUNTEDSUMM-DRIVER_TIMECOUNT',
                    'ORDER_ID' => $orderId,
                ]
            ),
        ];

        $response = $this->file_get_contents_with_logging(
            'https://sk-taxi.ru/tmapi/api.php?method=%2Ftm_tapi%2F1.0%2Fget_info_by_order_id',
            $params
        );
        return json_decode($response);
    }

    public function getCrewCoords($crewId)
    {
        $params = [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode(
                [
                    'crew_id' => $crewId,
                ]
            ),
        ];

        $response = json_decode(
            $this->file_get_contents_with_logging(
                'https://sk-taxi.ru/tmapi/api.php?method=/common_api/1.0/get_crews_coords',
                $params
            )
        );

        if ($response->code != 0) {
            return null;
        } else {
            return collect($response->data->crews_coords)->first();
        }
    }

    private function file_get_contents_with_logging($url, $params)
    {
        $success = false;
        do {
            try {
                $context = stream_context_create(['http' => $params]);
                $log = LogApi::newLogApi($url, json_encode($params, JSON_UNESCAPED_UNICODE));
                $result = file_get_contents($url, false, $context);
                $log->result = $result;
                $log->save();
                $success = true;
            } catch (Exception $exception) {
                sleep(1);
                $success = false;
            }
        } while (!$success);

        return $result;
    }
}