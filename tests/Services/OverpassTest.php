<?php

namespace Tests\Services;

use App\Models\OsmInfo;
use App\Models\Branch;
use App\Services\Overpass;
use Tests\TestCase;

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
    public function testOsmInfoOne()
    {
        $place = new Branch('way', 162817836);
        $osmInfo = $this->getInstance()->fetchOsmInfo([ $place ])[0];
        self::assertInstanceOf(OsmInfo::class, $osmInfo);
        self::assertEquals('Bandira Addis Map Entertainment PLC', $osmInfo->tags->name);
    }

    public function testOsmInfoMany()
    {
        $place1 = new Branch('node', 3959878839);
        $place2 = new Branch('way', 798092378);
        $osmInfo = $this->getInstance()->fetchOsmInfo([ $place1, $place2 ]);

        self::assertInstanceOf(OsmInfo::class, $osmInfo[0]);
        self::assertEquals('Zemen Bank', $osmInfo[0]->tags->name);
        self::assertEquals('Zemen Bank (Future Headquarters) (Under Construction)', $osmInfo[1]->tags->name);

    }
}
