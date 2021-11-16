<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\TakingAddressConversation;

class TakingAddressForFavoriteRouteConversation extends TakingAddressConversation
{
    public const ADDED_ROUTE_CONVERSATION = 'App\Conversations\FavoriteRoutes\AddedRouteMenuConversation';

    public function needToSaveAddressToHistory()
    {
        return false;
    }
}