<?php

namespace Tests\Unit;

use App\Models\FavoriteRoute;
use App\Models\User;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Storages\Drivers\FileStorage;
use BotMan\BotMan\Storages\Storage;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;


class ExampleTest extends TestCase
{
    use TakingAddressTrait;

    public $options;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $storage = new Storage(new FileStorage());
        $storage->save([1]);

        $storage2 = new Storage(new FileStorage());
    }

    public function getUser()
    {
        return User::first();
    }

    public function testComplexQuestion()
    {
        $question = ComplexQuestion::createWithSimpleButtons('text', [ButtonsStructure::BACK]);
        /** @var ComplexQuestion $question */
        $question = ComplexQuestion::addOrderHistoryButtons($question, User::first()->orders);
  
        FavoriteRoute::create([
                                  'user_id' => 1,
                                  'name' => 'name',
                                  'address' => User::first()->getOrderInfoByImplodedAddress(
                                      $question->getButtons()[5]['text']
                                  )->toJson(JSON_UNESCAPED_UNICODE)
                              ]);
    }

    public function testCreateOrderFromFavoriteRoute()
    {
//        $route = $this->getUser()->favoriteRoutes->where('name', 'name')->first();
//        $addressInfo = collect(json_decode($route->address));
//        $addressInfo['address'] = explode('-', $addressInfo['address']);
//        $storage = new Storage(new FileStorage());
//        $storage->delete();
//        $storage->save($addressInfo->toArray());
//        $storage->save(
//            ['crew_group_id' => (new Options())->getCrewGroupIdFromCity($this->getUser()->city)]
//        );
        $route = FavoriteRoute::where('name', 'name')->where('user_id', '1')->first();
        if ($route) {
            $route->delete();
        }
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . './../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
