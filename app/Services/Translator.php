<?php


namespace App\Services;


use App\Models\Translations\LangKey;
use App\Models\Translations\LangPackage;
use App\Models\Translations\LangTranslations;
use Illuminate\Support\Collection;

class Translator
{
    private $lang;

    /**
     * @param $lang
     */
    public function __construct($lang)
    {
        $this->lang = $lang;
    }

    public function trans($key, $replace = [])
    {
        return LangTranslations::where(['key_id' => LangKey::where('key', $key)->first()->id, 'package_id' => $this->getLang()]);
    }


    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param mixed $lang
     */
    public function setLang($lang): void
    {
        $this->lang = $lang;
    }






    const BUTTONS_FILE_NAME = "../resources/lang/ru/buttons.php";
    const MESSAGES_FILE_NAME = "../resources/lang/ru/messages.php";
//
//
//    /**
//     * @var Collection
//     */
//    private  $words;
//
//    /**
//     * @var string
//     */
//    private $fileName;

//    public static function createButtonsEditor()
//    {
//        $editor = new self();
//        $editor->loadButtons();
//        $editor->fileName = self::BUTTONS_FILE_NAME;
//
//        return $editor;
//    }
//
//    public static function createMessagesEditor()
//    {
//        $editor = new self();
//        $editor->loadMessages();
//        $editor->fileName = self::MESSAGES_FILE_NAME;
//
//        return $editor;
//    }
//
//    public function editWord($key, $value)
//    {
//        $this->words->put($key, $value);
//    }
//
//
//    private  function loadMessages()
//    {
//       $this->words = collect(include (self::MESSAGES_FILE_NAME));
//    }
//
//    private  function loadButtons()
//    {
//        $this->words = collect(include (self::BUTTONS_FILE_NAME));
//    }
//
//    public function save()
//    {
//        file_put_contents($this->fileName,  '<?php return ' . var_export($this->words->toArray(), true) . ';');
//    }
//
//
//    public function getWords()
//    {
//        return $this->words;
//    }
//
//    /**
//     * @param Collection $words
//     */
//    public function setWords(Collection $words): void
//    {
//        $this->words = $words;
//    }



}