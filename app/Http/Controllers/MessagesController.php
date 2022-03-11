<?php

namespace App\Http\Controllers;


use App\Models\User;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function index()
    {
        return view('adminPanel.messages');
    }

    public function sendMessages(Request $request)
    {
        $users = User::whereNull('isBlocked')->get()->transform(function (User $user) {
            return [
                'telegram_id' => $user->telegram_id,
                'vk_id' => $user->vk_id,
            ];
        });
        $botman = resolve('botman');
        if ($request->type === 'all' || $request->type === 'telegram') {
            $botman->say(new OutgoingMessage('Рассылка'), $users->pluck('telegram_id'), TelegramDriver::class);
        }

        if ($request->type === 'all' || $request->type === 'vk') {
            $botman->say(new OutgoingMessage('Рассылка'), $users->pluck('vk_id'), VkCommunityCallbackDriver::class);
        }

        return back()->with('ok', 'Рассылка выполнена успешно');
    }
}