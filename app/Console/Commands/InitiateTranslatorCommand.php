<?php

namespace App\Console\Commands;

use App\Models\Config;
use App\Models\Translations\LangKey;
use App\Models\Translations\LangPackage;
use App\Models\Translations\LangTranslations;
use App\Services\Translator;
use Illuminate\Console\Command;

class InitiateTranslatorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'initiateTranslator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $messages = collect(include (Translator::MESSAGES_FILE_NAME));
        foreach (Config::MESSAGE_LABELS as $key => $message) {
            LangKey::create(
                [
                    'key' => 'messages.' .$key,
                    'description' => $message,
                    'default_value' => $messages[$key],
                ]
            );
        }

        foreach (collect(include (Translator::BUTTONS_FILE_NAME)) as $key => $button) {
            LangKey::create(
                [
                    'key' => 'buttons.' .$key,
                    'description' => $key,
                    'default_value' => $button,
                ]
            );
        }

        $package = LangPackage::create(['name' => 'Основной русский пакет', 'is_default' => true]);

        $keys = LangKey::all();
        foreach ($keys as $key) {
            LangTranslations::create(
                [
                    'package_id' => $package->id,
                    'key_id' => $key->id,
                    'translate' => $key->default_value,
                ]
            );
        }
    }
}
