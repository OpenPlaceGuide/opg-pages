<?php

namespace App\Models;

use stdClass;

class OsmInfo
{
    public function __construct(public readonly float $lat, public readonly float $lon, public readonly stdClass $tags)
    {
    }
}
