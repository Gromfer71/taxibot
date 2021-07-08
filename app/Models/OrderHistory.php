<?php

namespace App\Models;

use App\Services\OrderApiService;
use BotMan\BotMan\BotMan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereChangedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereFailReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereRelevance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderHistory whereWishes($value)
 * @mixin \Eloquent
 */
class OrderHistory extends Model
{
    protected $guarded = [];

    protected $table = 'orders_history';

    const NEW_ORDER = 100;
    const DRIVER_ASSIGNED = 7;
    const CAR_AT_PLACE = 10;
    const CLIENT_INSIDE = 11;
    const ABORTED_BY_DRIVER = 93;
    const ABORTED = 5;
    const FINISHED = 79;
    const FINISHED_BY_DRIVER = 4;
    const CLIENT_DONT_COME_OUT = 39;
    const CLIENT_DONT_COME_OUT_2 = 12;
    const CLIENT_DONT_COME_OUT_3 = 60;
    const ORDER_NOT_FOUND = -1;
    const REQUEST_FOR_ABORT_BY_DRIVER = 65;
    const IN_QUEUE = 3;
    const QUEUE_ABORTED_BY_DRIVER = 36;
    const ORDER_SENDED_TO_DRIVER = 13;
    const ORDER_RECIEVED_BY_DRIVER = 14;
    const DRIVER_ABORTED_FROM_ORDER = 1;

    public static function newOrder(BotMan $bot, $useBonus = false)
	{
		$api = new OrderApiService();
		$response = $api->createOrder($bot, $useBonus);

		if($response['code'] === 0) {
			return self::create([
				'id' => $response['data']['order_id'],
				'user_id' => $bot->getUser()->getId(),
				'address' => collect($bot->userStorage()->get('address'))->implode(' - '),
				'price' => $bot->userStorage()->get('price'),
				'changed_price' => $bot->userStorage()->get('changed_price') ? $bot->userStorage()->get('changed_price')['id']:null ,
				'comment' => $bot->userStorage()->get('comment'),
				'wishes' => collect($bot->userStorage()->get('wishes'))->implode(', '),
				'relevance' => 0,
                'usebonus' => $useBonus,
                'platform' => $bot->getDriver()->getName()
			]);
		} else {
			return $response;
		}

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

	public static function getActualOrder($userId)
	{
		return self::where(['user_id' =>  $userId, 'relevance' => 0])->get()->first();
	}

	public static function getAllActualOrders()
	{
		return self::where('relevance', 0)->get();
	}

	public static function cancelAllOrders($userId)
	{
		self::where(['user_id' =>  $userId, 'relevance' => 0])->update(['relevance' => -1, 'fail_reason' => 'Пользователь перешел в главное меню, отменились все заявки']);
	}

	public function updateOrderState()
	{
        $api = new OrderApiService();
	    $newState = $api->getOrderState($this);
	    if ($newState->code != 0) return false;
        //Другой запрос для обработки механизма удаления заказов, который не меняет state_id
        if ($newState->data->finish_time && $newState->data->state_id != self::FINISHED && $newState->data->state_id != self::FINISHED_BY_DRIVER && $newState->data->state_id != self::ABORTED){
            $currentOrders =  $api->getCurrentOrders(User::find($this->user_id));
            $finded = false;
            foreach ($currentOrders->data->orders as $currentOrder){
                if ($currentOrder->id == $this->id)   $finded = true;
            }
            if (!$finded) return false;
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

		if($state) {
			return $state->car_color . ' ' . $state->car_mark . ' ' . $state->car_model . ' ' . $state->car_number;
		} else {
			return null;
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
        $api->changeOrderPrice($bot,$this);
    }

    public function wasInQueue(){
        if (!$this->state_id_chain || empty($this->state_id_chain)) return false;
        $chain = json_decode($this->state_id_chain);
        if (!is_array($chain)) return false;
        return in_array(self::IN_QUEUE,$chain);
    }

    public function asAbortedFromQueue(){
        if (!$this->state_id_chain || empty($this->state_id_chain)) return false;
        $chain = json_decode($this->state_id_chain);
        if (!is_array($chain)) return false;
        $chain = collect($chain);
        if ($chain->count() < 2) return false;

        $last = $chain->pop();
        $lastm1 = $chain->pop();
        if ($last == self::QUEUE_ABORTED_BY_DRIVER && $lastm1 == self::IN_QUEUE) return true;

        if ($chain->count() < 1) return false;
        $lastm2 = $chain->pop();
        if ($last == self::QUEUE_ABORTED_BY_DRIVER  && $lastm1 == self::ORDER_SENDED_TO_DRIVER && $lastm2 == self::IN_QUEUE) return true;
        if ($last == self::QUEUE_ABORTED_BY_DRIVER  && $lastm1 == self::ORDER_RECIEVED_BY_DRIVER && $lastm2 == self::IN_QUEUE) return true;

        if ($chain->count() < 1) return false;
        $lastm3 = $chain->pop();

        if ($last == self::QUEUE_ABORTED_BY_DRIVER   && $lastm2 == self::ORDER_SENDED_TO_DRIVER && $lastm1 == self::ORDER_RECIEVED_BY_DRIVER && $lastm3 == self::IN_QUEUE) return true;

        return false;



    }

    public function checkOrder()
    {
        $oldState = $this->getCurrentOrderState()->state_id ?? self::NEW_ORDER;
        $newState = $this->updateOrderState();
        if (!$newState) return self::ORDER_NOT_FOUND;
        if ($newState->state_id != $oldState) {
            $this->state = json_encode($newState);
            if (empty($this->state_id_chain))  $this->state_id_chain = json_encode([]);
            $old_state_id_chain = json_decode($this->state_id_chain);
            $old_state_id_chain[] = $newState->state_id;
            $this->state_id_chain = json_encode($old_state_id_chain);
            $this->save();
            return $newState->state_id;
        }
        return false;
    }
}
