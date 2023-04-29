<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Branch;
use App\Models\OsmInfo;
use App\Models\Place;
use App\Models\PoiType;
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
     * @return array<Branch>
     */
    private function createPlaces(mixed $branches): array
    {
        return collect($branches)->map(function (array $branch) {
            return new Branch($branch['type'], $branch['id']);
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

        return new PoiType($this, $slug, $parsed['logo'] ?? null, $parsed['icon'] ?? null, $parsed['color'] ?? null, $parsed['tags'], $parsed['name'], $parsed['plural']);
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

    public function getAreaInfo(string $areaSlug)
    {
        $yamlSource = file_get_contents($this->getAreaFileName($areaSlug));

        $parsed = Yaml::parse($yamlSource);

        return new Area($this, $areaSlug, $parsed['name'] ?? [], $parsed['description'] ?? [], $parsed['tags'], $parsed['color'] ?? 'gray');
    }

    public function listTypes(): array
    {
        $typeFiles = glob($this->getTypeFileName('*'));
        $result = [];
        foreach($typeFiles as $filename) {
            $slug = basename(dirname($filename));
            $result[] = $this->getTypeInfo($slug);
        }

        return $result;
    }

    /**
     * @return array<string, Place>
     */
    public function listPlaceIndex(): array
    {
        $typeFiles = glob($this->getPlaceFileName('*'));
        $result = [];
        foreach($typeFiles as $filename) {
            $slug = basename(dirname($filename));
            $placeInfo = $this->getPlaceInfo($slug);
            foreach($placeInfo->getKeys() as $key) {
                $result[$key] = $placeInfo;
            }

        }
        return $result;
    }

    public function resolvePlace(Branch $branch): ?Place
    {
        // FIXME: add caching
        $places = $this->listPlaceIndex();

        if (isset($places[$branch->getKey()])) {
            return $places[$branch->getKey()];
        }

        return null;
    }

    public function getUrl(OsmInfo $osmInfo)
    {
        $place = $this->resolvePlace($osmInfo->idInfo);

        if ($place !== null) {
            return $place->getUrl($osmInfo->idInfo);
        }

        return $osmInfo->idInfo->getUrl($osmInfo->tags->name ?? '');
    }
}
