<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Conversations\FavoriteRoutes\FavoriteRouteConversation;
use App\Conversations\Settings\SettingsConversation;
use App\Conversations\TakingAddressConversation;
use App\Models\OrderHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;

class MenuConversation extends BaseConversation
{
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::REQUEST_CALL => function () {
                $this->getUser()->setUserNeedDispatcher();
                $this->run(Translator::trans('messages.wait for dispatcher'));
            },
            ButtonsStructure::TAKE_TAXI => TakingAddressConversation::class,
            ButtonsStructure::PRICE_LIST => function () {
                $this->run(Translator::trans('messages.price list'));
            },
            ButtonsStructure::ALL_ABOUT_BONUSES => BonusesConversation::class,
            ButtonsStructure::BACK => 'run',
            ButtonsStructure::FAVORITE_ROUTES => FavoriteRouteConversation::class,
            ButtonsStructure::SETTINGS => SettingsConversation::class
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run($message = null)
    {
        $this->saveToStorage(['user_id' => $this->bot->getUser()->getId()]);
        $this->bot->userStorage()->delete();
        OrderHistory::cancelAllOrders($this->getUser()->id, $this->bot->getDriver()->getName());

        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.choose menu'),
            ButtonsStructure::getMainMenu(),
            ['config' => ButtonsFormatterService::MAIN_MENU_FORMAT]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }
}