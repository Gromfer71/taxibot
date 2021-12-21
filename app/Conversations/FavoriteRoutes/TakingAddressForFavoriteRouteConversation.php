<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\TakingAddressConversation;
use App\Services\Bot\ButtonsStructure;

class TakingAddressForFavoriteRouteConversation extends TakingAddressConversation
{
    public const ADDED_ROUTE_CONVERSATION = 'App\Conversations\FavoriteRoutes\AddedRouteMenuConversation';
    public $conversationAfterTakeAddress = self::ADDED_ROUTE_CONVERSATION;

    public function backButton()
    {
        return ButtonsStructure::BACK;
    }

    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => FavoriteRouteSettingsConversation::class,
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }


}