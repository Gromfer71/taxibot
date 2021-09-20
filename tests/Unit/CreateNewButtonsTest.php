<?php


use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class CreateNewButtonsTest extends TestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        $this->createApplication();
    }

    public function test_create_buttons()
    {
        dd(ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.welcome message'), ['buttons.start menu', 'buttons.start menu']));
    }
}
