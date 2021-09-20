<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Контроллер для управления настройками чат-бота через веб-панель
 */
class BotSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Главная страница настроек
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return View::make('adminPanel.bot_settings',
                          [
                              'admins' => Admin::all(),
                              'token' => Config::getToken(),
                              'config' => Config::getTaxibotConfig()
                          ]
        );
    }

    /**
     * Post. Сохранение токена
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeToken(Request $request): RedirectResponse
    {
        if ($request->get('token')) {
            Config::getToken()
                ->setValue($request->get('token'));

            return back()->with('ok', 'Успешно');
        } else {
            return back()->with('error', 'Вы не указали токен!');
        }
    }

    /**
     * Post. Сохранение токена в storage/app/taxi_config.json
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeConfigFile(Request $request): RedirectResponse
    {
        if ($request->get('config')) {
            Config::setTaxibotConfig($request->get('config'));

            return back()->with('ok', 'Успешно');
        } else {
            return back()->with('error', 'Вы не указали ссылку!');
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function editMessages(Request $request)
    {
        $translator = \App\Services\Translator::createMessagesEditor();
        if ($request->isMethod('POST')) {
            $translator->setWords(collect($request->get('messages')));
            $translator->save();

            return back()->with('ok', 'Сохранено!');
        }

        return view('adminPanel.edit_messages', ['labels' => $translator->getWords()]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function editButtons(Request $request)
    {
        $translator = \App\Services\Translator::createButtonsEditor();
        if ($request->isMethod('POST')) {
            $translator->setWords(collect($request->get('buttons')));
            $translator->save();

            return back()->with('ok', 'Сохранено!');
        }

        return view('adminPanel.edit_buttons', ['labels' => $translator->getWords()]);
    }
}
