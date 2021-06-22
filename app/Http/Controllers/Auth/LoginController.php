<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\OrderApiService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Utils;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function confirmLogin(Request $request)
    {

        if(!Admin::find($request->get('phone'))) {
            return back()->with('error', 'Пользователь с таким телефоном не найден!');
        }

        if($request->isMethod('POST')) {
            if($request->get('sms_code') == Admin::find($request->get('phone'))->sms_code) {

                if($this->attemptLogin($request, Admin::find($request->get('phone'))->sms_code)) {
                    return redirect(route('home'));
                } else {
                    return  back()->with('error', 'Упс, что-то пошло не так!');
                }
            } else {
                return  back()->with('error', 'Проверочный код неверный!');
            }
        }

        return view('auth.sms_confirm_login', ['phone' => $request->get('phone')]);
    }

    public function sendSms(Request $request, OrderApiService $apiService)
    {
        if(!Admin::find($request->get('phone'))) {
            return back()->with('error', 'Пользователь с таким телефоном не найден!');
        }
        $code = $apiService->getRandomSMSCode();
        $apiService->sendSMScode($request->get('phone'), $code);
        $user = Admin::find($request->get('phone'));
        $user->sms_code = $code;
        $user->save();

        return redirect(route('confirm_login', ['phone' => $request->get('phone'), 'sms_code' => $request->get('sms_code')]));
    }

    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'sms_code');
    }

    public function username()
    {
        return 'phone';
    }


}
