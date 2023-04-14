<?php

namespace Tests\Services;

use App\Services\Overpass;
use PHPUnit\Framework\TestCase;

class OverpassTest extends TestCase
{
    private function getInstance()
    {
        return new Overpass();
    }

    public function testInstance()
    {
        self::assertInstanceOf(Overpass::class, $this->getInstance());
    }
    public function testFetchFromXapi()
    {
        $tags = $this->getInstance()->fetchFromXapi();
        self::assertInstanceOf('\stdClass', $tags);
        self::assertEquals('Bandira Addis Map Entertainment PLC', $tags->name);
    }
}
