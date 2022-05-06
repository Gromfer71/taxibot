<?php

namespace App\Http\Controllers;

use App\Models\AddressHistory;
use App\Models\Admin;
use App\Models\Config;
use App\Models\User;
use Barryvdh\TranslationManager\Models\LangPackage;
use Carbon\Carbon;
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
        $users = User::whereNotNull('phone')->get();

        return view('users.users', ['users' => $users]);
    }

    public function user($id)
    {
        if ($user = User::where('id', $id)->with(['orders', 'addresses'])->first()) {
            $config = json_decode(Config::getTaxibotConfig());
            $prices = collect(array_merge($config->overpriceOptions, $config->overpriceOptionsAfterOrderCreated));
            $user->orders->transform(function ($item) {
                $item->created_at = Carbon::make($item->created_at)->timezone('Asia/Irkutsk');

                return $item;
            });

            return view('users.user', ['user' => $user, 'prices' => $prices]);
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function delete($id)
    {
        if ($user = User::where('id', $id)->first()) {
            $user->delete();

            return back()->with('ok', 'Пользователь успешно удален');
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function block($id)
    {
        if ($user = User::where('id', $id)->first()) {
            $user->block();

            return back()->with('ok', 'Пользователь успешно заблокирован');
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function unblock($id)
    {
        if ($user = User::where('id', $id)->first()) {
            $user->unblock();

            return back()->with('ok', 'Пользователь успешно разблокирован');
        } else {
            return back()->with('error', 'Пользователь не найден!');
        }
    }

    public function ordersClear($id)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return back()->with('error', 'Пользователь не найден!');
        }

        $user->orders()->delete();

        return back()->with('ok', 'История заказов успешно очищена!');
    }

    /**
     * @throws \BotMan\BotMan\Exceptions\Base\BotManException
     */
    public function reset($id)
    {
        $user = User::where('id', $id)->first();
        $user->should_reset = true;
        $user->save();

        return back()->with('ok', 'Пользователь успешно сброшен!');
    }

    public function addressesClear($id)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return back()->with('error', 'Пользователь не найден!');
        }

        $user->addresses()->delete();

        return back()->with('ok', 'История адресов успешно очищена!');
    }

    public function addUser(Request $request)
    {
        User::firstOrCreate(['phone' => $request->phone, 'lang_id' => LangPackage::where('code', 'ru')->first()->id]);

        return back();
    }

    public function deleteAddress($id)
    {
        $address = AddressHistory::find($id);

        if (!$address) {
            return back()->with('error', 'Упс, адрес не найден!');
        }

        $address->delete();

        return back()->with('ok', 'Адрес удален успешно!');
    }

    public function changePassword(Request $request)
    {
        $admin = Admin::find($request->get('phone'));
        if (!$admin) {
            return back();
        }

        $admin->password = md5($request->get('new_password'));
        $admin->save();

        return back()->with('ok', 'Пароль успешно изменен');
    }


}
