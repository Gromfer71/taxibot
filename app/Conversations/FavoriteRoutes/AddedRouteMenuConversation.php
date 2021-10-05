<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseAddressConversation;
use App\Models\FavoriteRoute;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\TakingAdditionalAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;

class AddedRouteMenuConversation extends BaseAddressConversation
{
    use TakingAdditionalAddressTrait;

    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::SAVE => 'setRouteName',
            ButtonsStructure::ADD_ADDRESS => 'addAdditionalAddress'
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.added favorite route menu') . ' ' . implode(
                ' - ',
                $this->bot->userStorage()->get(
                    'address'
                )
            ),
            [ButtonsStructure::SAVE, ButtonsStructure::ADD_ADDRESS]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }

    public function setRouteName()
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

            $this->bot->startConversation(new FavoriteRouteSettingsConversation());
        });
    }
}