<?php

namespace App\Services;

use App\Models\Area;
use App\Models\OsmId;
use App\Models\OsmInfo;
use App\Models\Place;
use App\Models\PoiType;
use Nette\Utils\Type;
use Symfony\Component\Yaml\Yaml;

class Repository
{
    public function __construct(public readonly string $name)
    {
    }

    public static function getInstance(): Repository
    {
        return (new self('ethiopia'));
    }

    public function getPlaceInfo($slug): Place
    {
        $yamlSource = file_get_contents($this->getPlaceFileName($slug));

        $parsed = Yaml::parse($yamlSource);

        $branches = $this->createPlaces($parsed['osmBranches'] ?? [$parsed['osm']]);

        $gallery = $parsed['gallery'] ?? [];

        return new Place($this, $slug, $parsed['logo'], $parsed['color'], $branches, $gallery);
    }

    private function getPlaceFileName($slug): string
    {
        return storage_path(
            sprintf('app/repositories/opg-data-%s/places/%s/place.yaml', $this->name, $slug)
        );
    }

    /**
     * @param array<array> $branches
     * @return array<OsmId>
     */
    private function createPlaces(mixed $branches): array
    {
        return collect($branches)->map(function (array $branch) {
            return new OsmId($branch['type'], $branch['id']);
        })->toArray();
    }

    /**
     * Check if the page name is actually a type (listing of POIs)
     */
    public function isType($slug)
    {
        return file_exists($this->getTypeFileName($slug));
    }

    private function getTypeFileName($slug): string|false
    {
        return (storage_path(
            sprintf('app/repositories/opg-data-%s/places/%s/type.yaml', $this->name, $slug)
        ));
    }

    public function getTypeInfo(string $slug)
    {
        $yamlSource = file_get_contents($this->getTypeFileName($slug));

        $parsed = Yaml::parse($yamlSource);

        return new PoiType($this, $slug, $parsed['logo'] ?? null, $parsed['icon'] ?? null, $parsed['color'] ?? null, $parsed['tags'], $parsed['name'], $parsed['description'] ?? [], $parsed['plural']);
    }

    public function isArea(string $slug): bool
    {
        return file_exists($this->getAreaFileName($slug));

    }

    private function getAreaFileName(string $slug): string
    {
        return (storage_path(
            sprintf('app/repositories/opg-data-%s/places/%s/area.yaml', $this->name, $slug)
        ));
    }

    public function getAreaInfo(string $areaSlug, bool $canEnrich = true)
    {
        $yamlSource = file_get_contents($this->getAreaFileName($areaSlug));

        $parsed = Yaml::parse($yamlSource);
        $osmId = isset($parsed['osm']) ? new OsmId($parsed['osm']['type'], $parsed['osm']['id']) : null;

        if ($canEnrich && $osmId !== null) {
            $area = $this->resolveArea($osmId->getAreaId());

            if ($area !== null) {
                return $area;
            }
        }

        return new Area($this, $osmId, $areaSlug, $parsed['name'] ?? [], $parsed['description'] ?? [], $parsed['color'] ?? 'gray', $parsed['sub_areas'] ?? []);
    }

    public function listTypes(): array
    {
        $types = $this->listTypesIncludingFallback();
        unset($types['~fallback_type']);
        return $types;
    }

    public function listTypesIncludingFallback(): array
    {
        return Cache::remember('types', function() {
            return $this->listTypesUncached();
        });

    }

    private function listTypesUncached(): array
    {
        $typeFiles = glob($this->getTypeFileName('*'));
        $result = [];
        foreach($typeFiles as $filename) {
            $slug = basename(dirname($filename));
            $result[$slug] = $this->getTypeInfo($slug);
        }

        return $result;
    }


    public function resolveType(OsmInfo $osmInfo): ?PoiType
    {
        $types = $this->listTypesIncludingFallback();

        // FIXME: sort by priority
        foreach($types as $type)
        {
            if ($osmInfo->matches($type)) {
                return $type;
            }
        }

        return null;
    }

    public function listPlaceIndex(): array
    {
        return Cache::remember('place', function() {
            return $this->listPlaceIndexUncached();
        });
    }

    /**
     * @return array<string, Place>
     */
    private function listPlaceIndexUncached(): array
    {
        $placeFiles = glob($this->getPlaceFileName('*'));
        $result = [];
        foreach($placeFiles as $filename) {
            $slug = basename(dirname($filename));
            $placeInfo = $this->getPlaceInfo($slug);
            foreach($placeInfo->getKeys() as $key) {
                $result[$key] = $placeInfo;
            }

        }
        return $result;
    }

    public function resolvePlace(OsmId $branch): ?Place
    {
        $places = $this->listPlaceIndex();

        if (isset($places[$branch->getKey()])) {
            return $places[$branch->getKey()];
        }

        return null;
    }

    public function isFeatured(OsmId $branch): bool
    {
        return $this->resolvePlace($branch) !== null;
    }

    public function getUrl(OsmInfo $osmInfo)
    {
        $place = $this->resolvePlace($osmInfo->idInfo);

        if ($place !== null) {
            return $place->getUrl($osmInfo->idInfo);
        }

        return $osmInfo->idInfo->getUrl($osmInfo->tags->name ?? '');
    }

    public function listAreas()
    {
        return Cache::remember('areas', function() {
            return $this->listAreasUncached();
        });
    }

    public function listLeafAreas()
    {
        $leafAreas = array_filter($this->listAreas(), function(Area $area) {
            return count($area->subAreas) === 0;
        });
        return $leafAreas;
    }

    private function listAreasUncached()
    {
        $areaFiles = glob($this->getAreaFileName('*'));
        $result = [];
        foreach($areaFiles as $filename) {
            $slug = basename(dirname($filename));
            $areaInfo = $this->getAreaInfo($slug, false);
            $result[$areaInfo->getKey()] = $areaInfo;
        }

        (new Overpass())->addTagsForAreas($result);
        return $result;
    }

    public function resolveArea($id)
    {
        $areas = $this->listAreas();

        return $areas[$id] ?? null;
    }
}
