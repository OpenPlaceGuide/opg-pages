<?php

namespace App\Models;

use App\Services\Language;
use Illuminate\Support\Facades\App;

class OsmId
{
    const SHORT_OSM_TYPES = [
        'n' => 'node',
        'w' => 'way',
        'r' => 'relation'
    ];
    readonly public string $osmType;

    public function __construct(string $osmType, readonly public int $osmId) {
        if (strlen($osmType) === 1) {
            $this->osmType = self::SHORT_OSM_TYPES[$osmType];
        } else {
            $this->osmType = $osmType;
        }
    }

    public function getKey()
    {
        return $this->osmType . $this->osmId;
    }


    public function getUrl($name)
    {
        $slug = Language::slug($name);
        return route('osmPlace.' . App::currentLocale(), ['osmTypeLetter' => $this->osmType[0], 'osmId' => $this->osmId, 'slug' => $slug]);
    }

    public function getOsmUrl($baseUrl = 'https://www.osm.org')
    {
        return sprintf('%s/%s/%s', $baseUrl, $this->osmType, $this->osmId);
    }
}
