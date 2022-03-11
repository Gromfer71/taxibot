<?php

namespace App\Http\Controllers;


use App\Models\User;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Http\Request;
use Storage;

class MessagesController extends Controller
{
    public function index()
    {
        return view('adminPanel.messages');
    }

    public function send(Request $request)
    {
        $file = null;
        if ($request->file('file')) {
            $request->file('file')->store('public/files');
            $file = new File(env('APP_URL') . Storage::putFile('public/files', $request->file('file')));
        }

        $users = User::where('isBlocked', 0)->get()->transform(function (User $user) {
            return [
                'telegram_id' => $user->telegram_id,
                'vk_id' => $user->vk_id,
            ];
        });
        $botman = resolve('botman');
        $message = new OutgoingMessage($request->get('message'));
        if ($file) {
            $message = $message->withAttachment($file);
        }
        if ($request->type === 'all' || $request->type === 'telegram') {
            $botman->say(
                $message,
                $users->pluck('telegram_id')->toArray(),
                TelegramDriver::class
            );
        }

        if ($request->type === 'all' || $request->type === 'vk') {
            $botman->say($message, $users->pluck('vk_id')->toArray(), VkCommunityCallbackDriver::class);
        }

        return back()->with('ok', 'Рассылка выполнена успешно');
    }
}