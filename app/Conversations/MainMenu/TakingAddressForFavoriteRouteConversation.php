<?php

namespace App\Conversations\MainMenu;

use App\Conversations\TakingAddressConversation;

class TakingAddressForFavoriteRouteConversation extends TakingAddressConversation
{
    public $conversationAfterTakeAddress = 'App\Conversations\MainMenu\FavoriteRouteConversation';
}