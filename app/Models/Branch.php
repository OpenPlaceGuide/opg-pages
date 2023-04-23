<?php

namespace App\Models;

use App\Services\Language;
use Illuminate\Support\Str;

class Branch
{
    public function __construct(readonly public string $osmType, readonly public int $osmId) {

    }

    public function getKey()
    {
        return $this->osmType . $this->osmId;
    }

    public function getUrl($name)
    {
        $slug = Str::slug(Language::transliterate($name));
        return route('osmPlace', ['osmTypeLetter' => $this->osmType[0], 'osmId' => $this->osmId, 'slug' => $slug]);
    }

    public function getOsmUrl()
    {
        return sprintf('https://www.osm.org/%s/%s', $this->osmType, $this->osmId);
    }
}
