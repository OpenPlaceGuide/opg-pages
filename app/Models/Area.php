<?php

namespace App\Models;

use App\Services\Repository;
use Illuminate\Support\Facades\App;

class Area
{
    public function __construct(
        public readonly Repository $repository,
        readonly public ?OsmId $idInfo,
        readonly public string $slug,
        readonly public array $names,
        readonly public array $descriptions,
        readonly public string $color
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
}
