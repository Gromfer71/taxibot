<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\TakingAddressConversation;

class TakingAddressForFavoriteRouteConversation extends TakingAddressConversation
{
    public const ADDED_ROUTE_CONVERSATION = 'App\Conversations\FavoriteRoutes\AddedRouteMenuConversation';
    public const NEED_TO_SAVE_ADDRESS_HISTORY = false;

    public function needToSaveAddressToHistory()
    {
        return false;
    }
}