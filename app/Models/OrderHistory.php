<?php

namespace App\Models;

use App\Services\MessageGeneratorService;
use App\Services\OrderApiService;
use BotMan\BotMan\BotMan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\OrderHistory
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $address
 * @property int|null $price
 * @property int|null $changed_price
 * @property string|null $comment
 * @property string|null $wishes
 * @property string|null $state_id_chain
 * @property int|null $relevance
 * @property string|null $state
 * @property string|null $fail_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|OrderHistory newModelQuery()
 * @method static Builder|OrderHistory newQuery()
 * @method static Builder|OrderHistory query()
 * @method static Builder|OrderHistory whereAddress($value)
 * @method static Builder|OrderHistory whereChangedPrice($value)
 * @method static Builder|OrderHistory whereComment($value)
 * @method static Builder|OrderHistory whereCreatedAt($value)
 * @method static Builder|OrderHistory whereFailReason($value)
 * @method static Builder|OrderHistory whereId($value)
 * @method static Builder|OrderHistory wherePrice($value)
 * @method static Builder|OrderHistory whereRelevance($value)
 * @method static Builder|OrderHistory whereState($value)
 * @method static Builder|OrderHistory whereUpdatedAt($value)
 * @method static Builder|OrderHistory whereUserId($value)
 * @method static Builder|OrderHistory whereWishes($value)
 * @mixin \Eloquent
 * @property int $usebonus
 * @property string $platform
 * @property-read \App\Models\User $user
 * @method static Builder|OrderHistory wherePlatform($value)
 * @method static Builder|OrderHistory whereStateIdChain($value)
 * @method static Builder|OrderHistory whereUsebonus($value)
 */
class OrderHistory extends Model
{
    public const NEW_ORDER = 100;
    public const    DRIVER_ASSIGNED = 7;
    public const CAR_AT_PLACE = 10;
    public const CLIENT_INSIDE = 11;
    public const ABORTED_BY_DRIVER = 93;
    public const ABORTED = 5;
    public const FINISHED = 79;
    public const FINISHED_BY_DRIVER = 4;
    public const CLIENT_DONT_COME_OUT = 39;
    public const CLIENT_DONT_COME_OUT_2 = 12;
    public const CLIENT_DONT_COME_OUT_3 = 60;
    public const ORDER_NOT_FOUND = -1;
    public const REQUEST_FOR_ABORT_BY_DRIVER = 65;
    public const IN_QUEUE = 3;
    public const QUEUE_ABORTED_BY_DRIVER = 36;
    public const ORDER_SENDED_TO_DRIVER = 13;
    public const ORDER_RECIEVED_BY_DRIVER = 14;
    public const DRIVER_ABORTED_FROM_ORDER = 1;
    protected $guarded = [];
    protected $table = 'orders_history';

    public static function newOrder(BotMan $bot, $useBonus = false)
    {
        $api = new OrderApiService();
        $response = $api->createOrder($bot, $useBonus);

        if ($response['code'] === 0) {
            return self::create([
                                    'id' => $response['data']['order_id'],
                                    'user_id' => User::find($bot->getUser()->getId())->id,
                                    'address' => json_encode(
                                        [
                                            'address' => $bot->userStorage()->get('address'),
                                            'lat' => $bot->userStorage()->get('lat'),
                                            'lon' => $bot->userStorage()->get('lon')
                                        ],
                                        JSON_UNESCAPED_UNICODE
                                    ),
                                    'price' => $bot->userStorage()->get('price'),
                                    'changed_price' => $bot->userStorage()->get('changed_price') ? $bot->userStorage()->get('changed_price')['id'] : null,
                                    'comment' => $bot->userStorage()->get('comment'),
                                    'wishes' => MessageGeneratorService::implodeWishes(
                                        collect($bot->userStorage()->get('wishes'))
                                    ),
                                    'relevance' => 0,
                                    'usebonus' => $useBonus,
                                    'platform' => $bot->getDriver()->getName()
                                ]);
        } else {
            return null;
        }
    }

    public static function getActualOrder($userId, $driverName)
    {
        $user = User::find($userId);
        if (!$user) {
            $user = User::where('id', $userId)->first();
        }

        return self::Where(['user_id' => $user->id ?? 0, 'relevance' => 0, 'platform' => $driverName])->get()->first();
    }

    public static function getAllActualOrders()
    {
        return self::where('relevance', 0)->get();
    }

    public static function cancelAllOrders($userId, $driverName)
    {
        self::where(['user_id' => $userId, 'relevance' => 0, 'platform' => $driverName])->update(
            ['relevance' => -1, 'fail_reason' => 'Пользователь перешел в главное меню, отменились все заявки']
        );
    }

    public function cancelOrder()
    {
        $this->relevance = -1;
        $this->fail_reason = 'Пользователь сам отменил заявку';
        $this->save();
        $api = new OrderApiService();
        $api->cancelOrder($this);
        $this->updateOrderState();
    }

    public function setAbortedOrder()
    {
        $this->relevance = -1;
        $this->fail_reason = 'Отмена заказа диспетчером или водителем';
        $this->save();
    }

    public function setDeletedOrder()
    {
        $this->relevance = -1;
        $this->fail_reason = 'Заказ удален';
        $this->save();
    }

    public function updateOrderState($state = null)
    {
        $api = new OrderApiService();
        // $newState = $api->getOrderState($this);
        //$newState = $api->getOrderState($this);
        if (!$state) {
            $newState = $api->getOrderState($this);
        } else {
            $newState = $state;
        }

        if ($newState->code != 0 && $newState->code != 12) {
            return false;
        }
        if ($newState->code == 12) {
            return 12;
        }
        //Другой запрос для обработки механизма удаления заказов, который не меняет state_id
        if ($newState->data->finish_time && $newState->data->state_id != self::FINISHED && $newState->data->state_id != self::FINISHED_BY_DRIVER && $newState->data->state_id != self::ABORTED) {
            $currentOrders = $api->getCurrentOrders(User::where('id', $this->user_id)->first());
            $finded = false;
            foreach ($currentOrders->data->orders as $currentOrder) {
                if ($currentOrder->id == $this->id) {
                    $finded = true;
                }
            }
            if (!$finded) {
                return false;
            }
        }


        $this->state = json_encode($newState->data);
        $this->save();
        return json_decode($this->state);
    }

    public function getCurrentOrderState()
    {
        return json_decode($this->state);
    }

    public function getAutoInfo()
    {
        $state = $this->getCurrentOrderState();

        if ($state) {
            return $state->car_color . ' ' . $state->car_mark . ' ' . $state->car_model . ' ' . $state->car_number;
        } else {
            return '';
        }
    }

    public function finishOrder()
    {
        $this->relevance = 1;
        $this->save();
        $api = new OrderApiService();
        $api->finishOrder($this);
        $this->updateOrderState();
    }

    public function confirmOrder()
    {
        $api = new OrderApiService();
        $api->changeOrderState($this, OrderApiService::ORDER_CONFIRMED_BY_USER);
    }

    public function changePrice(BotMan $bot)
    {
        $api = new OrderApiService();
        $api->changeOrderPrice($bot, $this);
    }

    public function wasInQueue()
    {
        if (!$this->state_id_chain || empty($this->state_id_chain)) {
            return false;
        }
        $chain = json_decode($this->state_id_chain);
        if (!is_array($chain)) {
            return false;
        }
        return in_array(self::IN_QUEUE, $chain);
    }

    public function asAbortedFromQueue()
    {
        if (!$this->state_id_chain || empty($this->state_id_chain)) {
            return false;
        }
        $chain = json_decode($this->state_id_chain);
        if (!is_array($chain)) {
            return false;
        }
        $chain = collect($chain);
        if ($chain->count() < 2) {
            return false;
        }

        $last = $chain->pop();
        $lastm1 = $chain->pop();
        if ($last == self::QUEUE_ABORTED_BY_DRIVER && $lastm1 == self::IN_QUEUE) {
            return true;
        }

        if ($chain->count() < 1) {
            return false;
        }
        $lastm2 = $chain->pop();
        if ($last == self::QUEUE_ABORTED_BY_DRIVER && $lastm1 == self::ORDER_SENDED_TO_DRIVER && $lastm2 == self::IN_QUEUE) {
            return true;
        }
        if ($last == self::QUEUE_ABORTED_BY_DRIVER && $lastm1 == self::ORDER_RECIEVED_BY_DRIVER && $lastm2 == self::IN_QUEUE) {
            return true;
        }

        if ($chain->count() < 1) {
            return false;
        }
        $lastm3 = $chain->pop();

        if ($last == self::QUEUE_ABORTED_BY_DRIVER && $lastm2 == self::ORDER_SENDED_TO_DRIVER && $lastm1 == self::ORDER_RECIEVED_BY_DRIVER && $lastm3 == self::IN_QUEUE) {
            return true;
        }

        return false;
    }

    public function checkOrder($newState)
    {
        // На этом моменте, если chain_id повторяются в бд, но по факту ничего не меняется, значит некорректно сделали json_decode
        // и взяли по умолчанию NEW_ORDER. Это обычно из-за того что в бд руками правили состояние заказа
        $oldState = $this->getCurrentOrderState()->state_id ?? self::NEW_ORDER;
        $newState = $this->updateOrderState($newState);

        if (!$newState) {
            return self::ORDER_NOT_FOUND;
        }

        if ($newState === 12) {
            return null;
        }
//        \Illuminate\Support\Facades\Log::info($newState->state_id);
//        \Illuminate\Support\Facades\Log::info($oldState);
        if ($newState->state_id != $oldState) {
            $this->state = json_encode($newState);
            if (empty($this->state_id_chain)) {
                $this->state_id_chain = json_encode([]);
            }
            $old_state_id_chain = json_decode($this->state_id_chain);
            $old_state_id_chain[] = $newState->state_id;
            $this->state_id_chain = json_encode($old_state_id_chain);
            $this->save();
            return $newState->state_id;
        }
        return false;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
