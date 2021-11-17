<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Google\Cloud\Core\Exception\BadRequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления администраторами чат-бота
 */
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Создание нового администратора
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request): RedirectResponse
    {
        $admin = Admin::create(['phone' => $request->get('phone')]);
        $admin->save();

        return back()->with('ok', 'Администратор создан');
    }

    /**
     * Удаление администратора
     *
     * @param $phone
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($phone): RedirectResponse
    {
        try {
            Admin::find($phone)::first()->delete();
        } catch (BadRequestException $e) {
            throw new $e();
        }

        return back()->with('ok', 'Администратор удален');
    }
}
