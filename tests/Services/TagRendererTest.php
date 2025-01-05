<?php

namespace Tests\Services;

use App\Models\Area;
use App\Models\OsmId;
use App\Models\OsmInfo;
use App\Services\Repository;
use App\Services\TagRenderer;
use Tests\TestCase;

class TagRendererTest extends TestCase
{
    public function testRender()
    {
        $zemen = $this->createOsmInfo();
        $texts = (new TagRenderer($zemen))->getTagTexts();
        self::assertNotEmpty($texts);
        self::assertStringContainsString('Mo-Sa 08:00-18:00', implode("\n", $texts));
        self::assertStringContainsString('A cash point/ATM (Automated Teller Machine) is available at this location.', implode("\n", $texts));

    }

    private function createOsmInfo()
    {
        return new OsmInfo(
            new OsmId('node', 262991780),
            8.9944064,
            38.7902098,
            (object)[
                'addr:city' => 'Addis Ababa',
                'addr:street' => 'Cameroon Street',
                'amenity' => 'bank',
                'atm' => 'yes',
                'name' => 'Zemen Bank Bole MedhaneAlem Branch',
                'name:am' => 'ዘመን ባንክ ቦሌ መድኃኔዓለም ቅ/ፍ',
                'opening_hours' => 'Mo-Sa 08:00-18:00',
            ],
        );
    }
}
