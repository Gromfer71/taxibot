<?php

namespace App;

class Botman extends \BotMan
{
    public function loadActiveConversation()
    {
        if (env('MAINTENANCE_MODE')) {
            $this->reply('В чат-боте ведутся технические работы. Приносим извинения за неудобства');
            die();
        }

        parent::loadActiveConversation();
    }
}