<?php

namespace App\Models;

use App\Facades\Fallback;
use App\Services\Repository;
use Illuminate\Support\Facades\App;

class Area
{
    public \stdClass $tags;

    public function __construct(
        public readonly Repository $repository,
        readonly public ?OsmId $idInfo,
        readonly public string $slug,
        readonly public array $names,
        readonly public array $descriptions,
        readonly public string $color,
        readonly public array $subAreas
    )
    {

    }

    public function getKey()
    {
        return $this->idInfo?->getAreaId() ?? $this->slug;
    }

    public function getUrl()
    {
        $url = route('page.' . App::currentLocale(), ['slug' => $this->slug]);
        return $url;
    }

    public function getFullName()
    {
        $result = Fallback::field($this->tags, 'name');

        if ($part = Fallback::field($this->tags, 'is_in:state')) {
            $result .= ' - ' . $part;
        }

        if ($part = Fallback::field($this->tags, 'is_in:country')) {
            $result .= ', ' . $part;
        }

        return $result;
    }
}
