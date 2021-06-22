<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\Translator;

class BotSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('adminPanel.bot_settings', ['admins' => Admin::all()->pluck('phone', 'phone'), 'token' => Config::getToken(), 'config' => Config::getTaxibotConfig()->value]);
    }

    public function changeToken(Request $request)
    {
        if($request->get('token')) {
            $token = Config::getToken();
            $token->setValue($request->get('token'));

            return back()->with('ok', 'Успешно');

        } else {
            return back()->with('error', 'Вы не указали токен!');
        }
    }

    public function changeConfigFile(Request $request)
    {
        if($request->get('config')) {
            $token = Config::getTaxibotConfig();

            $token->setValue($request->get('config'));

            return back()->with('ok', 'Успешно');

        } else {
            return back()->with('error', 'Вы не указали ссылку!');
        }
    }

    public function editMessages(Request $request)
    {
        $translator = \App\Services\Translator::createMessagesEditor();
        if($request->isMethod('POST')) {

            $translator->setWords(collect($request->get('messages')));
            $translator->save();

            return back()->with('ok', 'Сохранено!');
        }

        return view('adminPanel.edit_messages', ['labels' => $translator->getWords()]);
    }

    public function editButtons(Request $request)
    {
        $translator = \App\Services\Translator::createButtonsEditor();
        if($request->isMethod('POST')) {

            $translator->setWords(collect($request->get('buttons')));
            $translator->save();

            return back()->with('ok', 'Сохранено!');
        }

        return view('adminPanel.edit_buttons', ['labels' => $translator->getWords()]);
    }
}
