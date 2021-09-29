<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseConversation;
use App\Conversations\MainMenu\FavoriteRouteConversation;
use App\Models\FavoriteRoute;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class SetRouteNameConversation extends BaseConversation
{

    /**
     * @inheritDoc
     */
    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.write favorite route name'));

        return $this->ask($question, function (Answer $answer) {
            FavoriteRoute::create([
                                      'user_id' => $this->getUser()->id,
                                      'name' => $answer->getText(),
                                      'address' => json_encode(
                                          [
                                              'address' => $this->bot->userStorage()->get(
                                                  'address'
                                              ),

                                              'lat' => $this->bot->userStorage()->get('lat'),
                                              'lon' => $this->bot->userStorage()->get('lon')
                                          ],
                                          JSON_UNESCAPED_UNICODE
                                      ),
                                  ]);

            $this->bot->startConversation(new FavoriteRouteConversation());
        });
    }
}