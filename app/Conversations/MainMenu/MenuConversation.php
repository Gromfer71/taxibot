<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Models\OrderHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Главное меню бота
 */
class MenuConversation extends BaseConversation
{
    /**
     * Массив действий под определенную кнопку. Если значение это анонимная функция, то выполнится она, если имя метода,
     * то выполнится он в контексте текущего класса, если название класса (с полным путем), то запустится его Conversation.
     *
     * @param array $replaceActions
     * @return array
     */
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::REQUEST_CALL => function () {
                $this->getUser()->setUserNeedDispatcher();
                $this->run(Translator::trans('messages.wait for dispatcher'));
            },
            ButtonsStructure::CHANGE_PHONE => 'App\Conversations\MainMenu\ChangePhoneConversation',
            ButtonsStructure::TAKE_TAXI => 'App\Conversations\TakingAddressConversation',
            ButtonsStructure::CHANGE_CITY => 'changeCity',
            ButtonsStructure::PRICE_LIST => function () {
                $this->run(Translator::trans('messages.price list'));
            },
            ButtonsStructure::ALL_ABOUT_BONUSES => 'App\Conversations\MainMenu\BonusesConversation',
            ButtonsStructure::ADDRESS_HISTORY_MENU => 'App\Conversations\MainMenu\AddressesHistoryConversation',
            ButtonsStructure::FAVORITE_ADDRESSES_MENU => 'App\Conversations\FavoriteAddressesConversation',
            ButtonsStructure::BACK => 'run',
            ButtonsStructure::LANG_MENU => 'App\Conversations\MainMenu\SwitchLangConversation',
            ButtonsStructure::FAVORITE_ROUTES => 'App\Conversations\FavoriteRoutes\FavoriteRouteConversation'

        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * Главное меню
     *
     * @param null $message
     * @return MenuConversation
     */
    public function run($message = null): MenuConversation
    {
        // В главном меню обновляем язык вручную т.к. после изменения языка он меняется только после нажатия кнопки


        $this->bot->userStorage()->delete();
        OrderHistory::cancelAllOrders($this->getUser()->id, $this->bot->getDriver()->getName());

        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.choose menu'),
            ButtonsStructure::getMainMenu(),
            ['config' => ButtonsFormatterService::MAIN_MENU_FORMAT]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }


    /**
     * Изменение города (не путать с установлением начального города при регистрации). Отличий мало, но контекст другой
     *
     * @return \App\Conversations\MainMenu\MenuConversation
     */
    public function changeCity(): MenuConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.choose city', ['city' => $this->getUser()->city]),
            [ButtonsStructure::BACK],
            ['config' => ButtonsFormatterService::CITY_MENU_FORMAT]
        );

        $question = ComplexQuestion::setButtons(
            $question,
            $this->options->getCitiesArray(),
            [],
            true
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            if (in_array($answer->getText(), $this->options->getCitiesArray())) {
                $this->getUser()->updateCity($answer->getText());
                $this->run(Translator::trans('messages.city has been changed', ['city' => $answer->getText()]));
            } else {
                $this->say(Translator::trans('messages.city not found'));
                $this->changeCity();
            }
        });
    }
}