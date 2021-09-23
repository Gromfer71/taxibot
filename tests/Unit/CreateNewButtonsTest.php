<?php


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


}
