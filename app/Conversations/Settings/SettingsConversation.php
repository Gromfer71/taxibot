<?php

namespace App\Conversations\Settings;

use App\Conversations\BaseConversation;
use App\Conversations\FavoriteAddressesConversation;
use App\Conversations\FavoriteRoutes\FavoriteRouteSettingsConversation;
use App\Conversations\MainMenu\AddressesHistoryConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class SettingsConversation extends BaseConversation
{
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::CHANGE_PHONE => ChangePhoneConversation::class,
            ButtonsStructure::LANG_MENU => SwitchLangConversation::class,
            ButtonsStructure::CHANGE_CITY => 'changeCity',
            ButtonsStructure::BACK => MenuConversation::class,
            ButtonsStructure::FAVORITE_ROUTE_SETTINGS => FavoriteRouteSettingsConversation::class,
            ButtonsStructure::ADDRESS_HISTORY_MENU => AddressesHistoryConversation::class,
            ButtonsStructure::CLEAR_ORDERS_HISTORY_MENU => ClearOrdersHistoryConversation::class,
            ButtonsStructure::FAVORITE_ADDRESSES_MENU => FavoriteAddressesConversation::class,
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @inheritDoc
     */
    public function run($message = null)
    {
        Translator::$lang = $this->getUser()->lang->code ?? 'ru';
        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.settings menu'),
            ButtonsStructure::getSettingsMenu(),
            ['config' => ButtonsFormatterService::MAIN_MENU_FORMAT]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }

    /**
     * Изменение города (не путать с установлением начального города при регистрации). Отличий мало, но контекст другой
     *
     * @return \App\Conversations\Settings\SettingsConversation
     */
    public function changeCity(): SettingsConversation
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
            if ($this->handleAction($answer, [ButtonsStructure::BACK => 'run'])) {
                return;
            }
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