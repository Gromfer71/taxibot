<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\MessageGeneratorService;
use Illuminate\Http\Request;

class UserController extends Controller
{

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::whereNotNull('phone')->whereNotNull('username')->get();
        $users->each(function ($user) {
            $user->userinfo = null;
        });

        return view('users.users', ['users' => $users->toJson()]);
    }

    public function user($id)
    {
        if ($user = User::find($id)) {
            $orders = $user->orders;

            $orders->each(function ($item) use ($user) {
                $item->phone = $user->phone;
                $item->state = null;
                $item->address = MessageGeneratorService::escape($item->address);
                $item->wishes = MessageGeneratorService::escape($item->wishes);
                $item->comment = MessageGeneratorService::escape($item->comment);
            });
            $config = json_decode(Config::getTaxibotConfig()->value);
            $prices = array_merge($config->overpriceOptions, $config->overpriceOptionsAfterOrderCreated);

            return view('users.user', ['orders' => $orders->toJson(), 'addresses' => json_encode($user->addresses, JSON_UNESCAPED_SLASHES), 'user' => $user, 'prices' => json_encode($prices)]);
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }

    }

    public function delete($id)
    {
        if ($user = User::find($id)) {
            $user->delete();

            return back()->with('ok', 'Пользователь успешно удален');
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function block($id)
    {
        if ($user = User::find($id)) {
            $user->block();

            return back()->with('ok', 'Пользователь успешно заблокирован');
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function unblock($id)
    {
        if ($user = User::find($id)) {
            $user->unblock();

            return back()->with('ok', 'Пользователь успешно разблокирован');
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function ordersClear($id)
    {
        $user = User::find($id);
        if(!$user) {
            return back()->with('error', 'Пользователь не найден!');
        }

        $user->orders()->delete();

        return back()->with('ok', 'История заказов успешно очищена!');
    }

    public function addressesClear($id)
    {
        $user = User::find($id);
        if(!$user) {
            return back()->with('error', 'Пользователь не найден!');
        }

        $user->addresses()->delete();

        return back()->with('ok', 'История адресов успешно очищена!');
    }
}
