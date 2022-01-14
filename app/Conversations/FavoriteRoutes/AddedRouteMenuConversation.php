<?php

namespace App\Conversations\FavoriteRoutes;

use App\Conversations\BaseAddressConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Conversations\TakingAdditionalAddressConversation;
use App\Models\FavoriteRoute;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class AddedRouteMenuConversation extends BaseAddressConversation
{

    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::CANCEL => FavoriteRouteSettingsConversation::class,
            ButtonsStructure::SAVE => 'setRouteName',
            ButtonsStructure::ADD_ADDRESS => function () {
                $this->bot->userStorage()->save(['additional_address_for_favorite_route' => true]);
                $this->bot->startConversation(new TakingAdditionalAddressConversation());
            }
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
            [ButtonsStructure::SAVE, ButtonsStructure::CANCEL]
        );
        if (!$this->getFromStorage('order_already_done')) {
            $question = ComplexQuestion::setButtons($question, [ButtonsStructure::ADD_ADDRESS]);
        }

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer) ?: $this->run();
        });
    }

    public function setRouteName()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.write favorite route name'), [ButtonsStructure::CANCEL, ButtonsStructure::SAVE]);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == ButtonsStructure::CANCEL) {
                $this->bot->startConversation(new FavoriteRouteSettingsConversation());
                return;
            } elseif ($answer->getValue() == ButtonsStructure::SAVE) {
                $this->setRouteName();
                return;
            } elseif ($answer->getValue() == ButtonsStructure::ADD_ADDRESS) {
                $this->setRouteName();
                return;
            }
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
                                      'crew_group_id' => $this->bot->userStorage()->get('crew_group_id')
                                  ]);

            if ($this->bot->userStorage()->get('order_already_done')) {
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->bot->startConversation(new FavoriteRouteSettingsConversation());
            }
        });
    }
}