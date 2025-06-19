<?php

namespace Tests\Services;

use App\Models\OsmInfo;
use App\Models\OsmId;
use App\Services\Overpass;
use App\Services\Repository;
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
        $place = new OsmId('node', 12749724203);
        $osmInfo = $this->getInstance()->fetchOsmInfo([ $place ])[0];
        self::assertInstanceOf(OsmInfo::class, $osmInfo);
        self::assertEquals('Bandira Addis Map Entertainment PLC HQ', $osmInfo->tags->name);
    }

    public function testOsmInfoMany()
    {
        $place1 = new OsmId('node', 3959878839);
        $place2 = new OsmId('way', 798092378);
        $osmInfo = $this->getInstance()->fetchOsmInfo([ $place1, $place2 ], Repository::getInstance()->listLeafAreas());

        self::assertInstanceOf(OsmInfo::class, $osmInfo[0]);
        self::assertEquals('Zemen Bank', $osmInfo[0]->tags->name);
        self::assertEquals('Zemen Bank (Future Headquarters) (Under Construction)', $osmInfo[1]->tags->name);

    }

    public function testAddTagsForAreasWithBoundingBox()
    {
        // Test that addTagsForAreas now includes bounding box information
        $areas = Repository::getInstance()->listAreas();

        // Find an area that should have bounding box data
        $testArea = null;
        foreach ($areas as $area) {
            if ($area->idInfo !== null) {
                $testArea = $area;
                break;
            }
        }

        if ($testArea !== null) {
            // Check that the area has bounding box information
            self::assertTrue($testArea->hasBoundingBox(), 'Area should have bounding box information');

            // Check that center coordinates can be calculated
            $center = $testArea->getCenterCoordinates();
            self::assertIsArray($center, 'Center coordinates should be an array');
            self::assertArrayHasKey('lat', $center, 'Center should have latitude');
            self::assertArrayHasKey('lon', $center, 'Center should have longitude');
            self::assertIsFloat($center['lat'], 'Latitude should be a float');
            self::assertIsFloat($center['lon'], 'Longitude should be a float');
        }
    }
}
