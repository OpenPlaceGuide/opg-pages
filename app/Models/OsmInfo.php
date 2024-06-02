<?php

namespace App\Models;

use stdClass;

class OsmInfo
{
    public function __construct(public readonly OsmId $idInfo, public readonly float $lat, public readonly float $lon, public readonly stdClass $tags, public readonly ?Area $area)
    {
    }

    public function matches(PoiType $type): bool
    {
        foreach($type->tags as $tag) {
            $key = $tag['key'];
            $value = $tag['value'];
            if (!isset($this->tags->{$key}) || mb_strtolower($this->tags->{$key}) != mb_strtolower($value)) {
                return false;
            }
        }

        return true;
    }
}
