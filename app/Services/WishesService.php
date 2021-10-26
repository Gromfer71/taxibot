<?php


namespace App\Services;


use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class WishesService
{
    public $wishesMenu;
    private $question;
    private $wishes;

    /**
     * wishes constructor.
     * @param $wishes
     * @param $question
     * @param $wishesMenu
     */
    public function __construct($wishes, $question, $wishesMenu)
    {
        $this->wishes = $wishes;
        $this->question = $question;
        $this->wishesMenu = $wishesMenu;
    }

    public function addButtonsToQuestion()
    {
        $carOptionsSelected = false;
        foreach ($this->wishesMenu['carOptions'] as $wish) {
            if (in_array($wish->name, $this->wishes->toArray())) {
                $carOptionsSelected = true;
                break;
            }
        }

        $changeOptionsSelected = false;
        foreach ($this->wishesMenu['changeOptions'] as $wish) {
            if (in_array($wish->name, $this->wishes->toArray())) {
                $changeOptionsSelected = true;
                break;
            }
        }

        if (!$changeOptionsSelected) {
            foreach ($this->wishesMenu['changeOptions'] as $wish) {
                $this->question->addButton(Button::create($wish->name));
            }
        }
        if (!$carOptionsSelected) {
            foreach ($this->wishesMenu['carOptions'] as $wish) {
                $this->question->addButton(Button::create($wish->name));
            }
        }
        foreach ($this->wishesMenu['wishOptions'] as $wish) {
            if (!in_array($wish->name, $this->wishes->toArray())) {
                $this->question->addButton(Button::create($wish->name));
            }
        }

        return $this->question;
    }

    public function getQuestion()
    {
        return $this->question;
    }


}