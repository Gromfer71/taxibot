<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\TakingAddressConversation;
use App\Services\Bot\ButtonsStructure;

class TakingAddressForFavoriteRouteConversation extends TakingAddressConversation
{
    public const ADDED_ROUTE_CONVERSATION = AddedRouteMenuConversation::class;
    public $conversationAfterTakeAddress = self::ADDED_ROUTE_CONVERSATION;

    public function backButton(): string
    {
        return ButtonsStructure::BACK;
    }

    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => function () {
                $this->saveToStorage(['go_to_add_route_menu' => true]);
                $this->bot->startConversation(new FavoriteRouteSettingsConversation());
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }


}