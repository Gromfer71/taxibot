<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Config;
use App\Services\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
                              'config' => Config::getTaxibotConfig(),
                              'welcomeFile' => Config::where(['name' => 'welcome_file'])->first(),
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
        $translator = Translator::createMessagesEditor();
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
        $translator = Translator::createButtonsEditor();
        if ($request->isMethod('POST')) {
            $translator->setWords(collect($request->get('buttons')));
            $translator->save();

            return back()->with('ok', 'Сохранено!');
        }

        return view('adminPanel.edit_buttons', ['labels' => $translator->getWords()]);
    }

    public function uploadWelcomeFile(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'max:51200|mimes:jpeg,jpg,png,mp3,mp4,avi,webm,m4a']);
        if ($validator->fails()) {
            return back()->with('error', 'Размер файла слишком большой или файл имеет недопустимый формат!');
        }
        Storage::deleteDirectory('bot');
        Storage::deleteDirectory('public/bot');
        $path = $request->file('file')->storeAs('public/bot', $request->file('file')->getClientOriginalName());
        Storage::putFileAs('/bot', $request->file('file'), $request->file('file')->getClientOriginalName());
        Config::updateOrCreate(['name' => 'welcome_file'], ['value' => $request->file('file')->getClientOriginalName()]);

        return back()->with('ok', 'Файл успешно сохранен');
    }

    public function deleteWelcomeFile()
    {
        Storage::deleteDirectory('bot');
        Storage::deleteDirectory('public/bot');
        $file = Config::where('name', 'welcome_file')->first();
        if ($file) {
            $file->delete();
        }

        return back()->with('ok', 'Файл успешно удален');
    }
}
