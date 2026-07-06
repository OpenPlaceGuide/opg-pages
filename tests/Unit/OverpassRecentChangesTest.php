<?php

namespace Tests\Unit;

use App\Services\Overpass;
use Tests\TestCase;

class OverpassRecentChangesTest extends TestCase
{
    public function testSinceDateIsRoundedToMidnightUtc(): void
    {
        $method = new \ReflectionMethod(Overpass::class, 'recentChangesSince');
        $since = $method->invoke(new Overpass(), 30);

        // A stable, day-granular date keeps the Overpass cache key stable.
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T00:00:00Z$/', $since);
    }

    public function testQueryFiltersAreaByDateAndOutputsMeta(): void
    {
        $method = new \ReflectionMethod(Overpass::class, 'buildRecentChangesQuery');
        $query = $method->invoke(new Overpass(), 3600000000, '2026-06-06T00:00:00Z');

        $this->assertStringContainsString('area(3600000000)->.a;', $query);
        $this->assertStringContainsString('nwr(area.a)[name](newer:"2026-06-06T00:00:00Z");', $query);
        $this->assertStringContainsString('out meta center;', $query);
    }
}
