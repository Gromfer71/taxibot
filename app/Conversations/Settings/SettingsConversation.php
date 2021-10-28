<?php

namespace App\Conversations\Settings;

use App\Conversations\BaseConversation;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class SettingsConversation extends BaseConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::CHANGE_PHONE => 'App\Conversations\Settings\ChangePhoneConversation',
            ButtonsStructure::LANG_MENU => 'App\Conversations\Settings\SwitchLangConversation',
            ButtonsStructure::CHANGE_CITY => 'changeCity',
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::FAVORITE_ROUTE_SETTINGS => 'App\Conversations\FavoriteRoutes\FavoriteRouteSettingsConversation',
            ButtonsStructure::ADDRESS_HISTORY_MENU => 'App\Conversations\MainMenu\AddressesHistoryConversation',
            ButtonsStructure::FAVORITE_ADDRESSES_MENU => 'App\Conversations\FavoriteAddressesConversation',
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
            $this->handleAction($answer, [ButtonsStructure::BACK => 'run']);
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