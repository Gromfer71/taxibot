<?php


namespace App\Services;


use Illuminate\Support\Collection;

class Translator
{
    const BUTTONS_FILE_NAME = "../resources/lang/ru/buttons.php";
    const MESSAGES_FILE_NAME = "../resources/lang/ru/messages.php";


    /**
     * @var Collection
     */
    private  $words;

    /**
     * @var string
     */
    private $fileName;

    public static function createButtonsEditor()
    {
        $editor = new self();
        $editor->loadButtons();
        $editor->fileName = self::BUTTONS_FILE_NAME;

        return $editor;
    }

    public static function createMessagesEditor()
    {
        $editor = new self();
        $editor->loadMessages();
        $editor->fileName = self::MESSAGES_FILE_NAME;

        return $editor;
    }

    public function editWord($key, $value)
    {
        $this->words->put($key, $value);
    }


    private  function loadMessages()
    {
       $this->words = collect(include (self::MESSAGES_FILE_NAME));
    }

    private  function loadButtons()
    {
        $this->words = collect(include (self::BUTTONS_FILE_NAME));
    }

    public function save()
    {
        file_put_contents($this->fileName,  '<?php return ' . var_export($this->words->toArray(), true) . ';');
    }


    public function getWords()
    {
        return $this->words;
    }

    /**
     * @param Collection $words
     */
    public function setWords(Collection $words): void
    {
        $this->words = $words;
    }



}