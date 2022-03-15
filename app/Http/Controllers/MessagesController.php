<?php

namespace App\Http\Controllers;


use App\Models\GlobalMessage;
use App\Models\User;
use App\Services\Options;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;

class MessagesController extends Controller
{
    public function index()
    {
        return view(
            'adminPanel.messages',
            [
                'cities' => (new Options())->getCities(),
                'phones' => User::whereNotNull('phone')->pluck('phone'),
                'messages' => GlobalMessage::all(),
            ]
        );
    }

    public function send(Request $request)
    {
        if (!$request->get('message') && !$request->file('file')) {
            return back()->with('error', 'Сообщение пустое!');
        }

        $validator = Validator::make($request->all(), ['file' => 'max:51200|mimes:jpeg,jpg,png,mp3,mp4,avi,webm,m4a']);
        if ($validator->fails()) {
            return back()->with('error', 'Размер файла слишком большой или файл имеет недопустимый формат!');
        }


        $file = null;
        $now = now()->timestamp;
        if ($request->file('file')) {
            $path = $request->file('file')->storeAs('public/files/' . $now . '/', $request->file('file')->getClientOriginalName());
            Storage::putFileAs('/files/' . $now . '/', $request->file('file'), $request->file('file')->getClientOriginalName());
            $url = env('APP_URL') . 'storage/files/' . $now . '/' . $request->file('file')->getClientOriginalName();
            if ($request->file('file')->extension() === 'mp3') {
                $file = new Audio(
                    $url,
                    $request->file('file')
                );
            } else {
                $file = new File(
                    $url,
                    $request->file('file')
                );
            }
        }

        $users = User::where('isBlocked', 0);
        if ($request->recipients === 'by_city') {
            $users->whereIn('city', $request->cities ?? []);
        } elseif ($request->recipients === 'by_phone') {
            $users->whereIn('phone', $request->phones ?? []);
        }

        if ($users->count() === 0) {
            return back()->with('ok', 'По данным критериям нет ни одного пользователя!');
        }

        $users = $users->get()->transform(function (User $user) {
            return [
                'telegram_id' => $user->telegram_id,
                'vk_id' => $user->vk_id,
            ];
        });
        $botman = resolve('botman');
        $message = new OutgoingMessage($request->get('message') ?: '  ');
        if ($file) {
            $message = $message->withAttachment($file);
        }
        if ($request->type === 'all' || $request->type === 'telegram') {
            if ($users->pluck('telegram_id')->filter()->isNotEmpty()) {
                $botman->say(
                    $message,
                    $users->pluck('telegram_id')->filter()->toArray(),
                    TelegramDriver::class
                );
            }
        }

        if ($request->type === 'all' || $request->type === 'vk') {
            if ($users->pluck('vk_id')->filter()->isNotEmpty()) {
                $botman->say($message, $users->pluck('vk_id')->filter()->toArray(), VkCommunityCallbackDriver::class);
            }
        }

        GlobalMessage::create([
                                  'admin_phone' => auth()->user()->phone,
                                  'platform' => $request->type,
                                  'recipients_type' => $request->recipients,
                                  'recipients' => json_encode($request->cities ?? ($request->phones ?? [])),
                                  'message' => $request->message ?? '',
                                  'file' => $url ?? null,
                                  'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                                  'created_at' => $now
                              ]);

        return back()->with('ok', 'Рассылка выполнена успешно');
    }

    public function deleteMessage($id): RedirectResponse
    {
        $message = GlobalMessage::findOrFail($id);
        Storage::deleteDirectory('files/'  . $message->created_at->timestamp);
        Storage::deleteDirectory('public/files/' . $message->created_at->timestamp . '/');

        $message->delete();

        return back()->with('ok', 'Сообщение удалено');
    }

    public function clearAllMessages(): RedirectResponse
    {
        GlobalMessage::all()->each(function (GlobalMessage $message) {
            $message->delete();
        });
        Storage::deleteDirectory('files');
        Storage::deleteDirectory('public/files');

        return back()->with('ok', 'Сообщения удалены');
    }
}