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
                              'welcomeFileTelegram' => Config::where(['name' => 'welcome_file_telegram'])->first(),
                              'welcomeFileVk' => Config::where(['name' => 'welcome_file_vk'])->first(),
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

    public function uploadWelcomeFileTelegram(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'max:12288|mimes:jpeg,jpg,png,mp3,mp4,avi,webm,m4a']);
        if ($validator->fails()) {
            return back()->with('error', 'Размер файла слишком большой или файл имеет недопустимый формат!');
        }
        Storage::deleteDirectory('telegram');
        Storage::deleteDirectory('public/telegram');
        $path = $request->file('file')->storeAs('public/telegram', $request->file('file')->getClientOriginalName());
        Storage::putFileAs('/telegram', $request->file('file'), $request->file('file')->getClientOriginalName());
        Config::updateOrCreate(['name' => 'welcome_file_telegram'], ['value' => $request->file('file')->getClientOriginalName()]);

        return back()->with('ok', 'Файл успешно сохранен');
    }

    public function deleteWelcomeFileTelegram()
    {
        Storage::deleteDirectory('telegram');
        Storage::deleteDirectory('public/telegram');
        $file = Config::where('name', 'welcome_file_telegram')->first();
        if ($file) {
            $file->delete();
        }

        return back()->with('ok', 'Файл успешно удален');
    }

    public function uploadWelcomeFileVk(Request $request)
    {
        $validator = Validator::make($request->all(), ['file' => 'max:12288|mimes:jpeg,jpg,png,mp3,mp4,avi,webm,m4a']);
        if ($validator->fails()) {
            return back()->with('error', 'Размер файла слишком большой или файл имеет недопустимый формат!');
        }
        Storage::deleteDirectory('vk');
        Storage::deleteDirectory('public/vk');
        $path = $request->file('file')->storeAs('public/vk', $request->file('file')->getClientOriginalName());
        Storage::putFileAs('/vk', $request->file('file'), $request->file('file')->getClientOriginalName());
        Config::updateOrCreate(['name' => 'welcome_file_vk'], ['value' => $request->file('file')->getClientOriginalName()]);

        return back()->with('ok', 'Файл успешно сохранен');
    }

    public function deleteWelcomeFileVk()
    {
        Storage::deleteDirectory('vk');
        Storage::deleteDirectory('public/vk');
        $file = Config::where('name', 'welcome_file_vk')->first();
        if ($file) {
            $file->delete();
        }

        return back()->with('ok', 'Файл успешно удален');
    }




}
