<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\TakingAddressConversation;

class TakingAddressForFavoriteRouteConversation extends TakingAddressConversation
{
    public $conversationAfterTakeAddress = 'App\Conversations\FavoriteRoutes\AddedRouteMenuConversation';
}