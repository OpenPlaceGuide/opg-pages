<?php

namespace App\Models;

use App\Facades\Fallback;
use App\Services\Repository;
use Illuminate\Support\Facades\App;

class Area
{
    public \stdClass $tags;
    public ?array $boundingBox = null;
    public array $mapillaryImages = [];

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

    /**
     * Get the center coordinates of the area from its bounding box
     *
     * @return array|null ['lat' => float, 'lon' => float] or null if no bounding box
     */
    public function getCenterCoordinates(): ?array
    {
        if (!$this->boundingBox) {
            return null;
        }

        return [
            'lat' => ($this->boundingBox['north'] + $this->boundingBox['south']) / 2,
            'lon' => ($this->boundingBox['east'] + $this->boundingBox['west']) / 2
        ];
    }

    /**
     * Check if the area has bounding box information
     */
    public function hasBoundingBox(): bool
    {
        return $this->boundingBox !== null;
    }

    /**
     * Get Mapillary images for this area
     */
    public function getMapillaryImages(): array
    {
        return $this->mapillaryImages;
    }

    /**
     * Set Mapillary images for this area
     */
    public function setMapillaryImages(array $images): void
    {
        $this->mapillaryImages = $images;
    }
}
