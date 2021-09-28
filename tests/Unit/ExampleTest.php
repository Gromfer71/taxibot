<?php

namespace Tests\Unit;

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
        $question = ComplexQuestion::addOrderHistoryButtons($question, User::first()->orders);
        dd($question);
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
