<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Place;
use App\Models\PoiType;
use Symfony\Component\Yaml\Yaml;

class Repository
{
    public function __construct(public readonly string $name)
    {
    }

    public function getPlaceInfo($slug): Place
    {
        $yamlSource = file_get_contents($this->getPlaceFileName($slug));

        $parsed = Yaml::parse($yamlSource);

        $branches = $this->createPlaces($parsed['osmBranches'] ?? [$parsed['osm']]);

        $gallery = $parsed['gallery'] ?? [];

        return new Place($this, $slug, $parsed['logo'], $branches, $gallery);
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

        return new PoiType($this, $slug, $parsed['logo'] ?? null, $parsed['tags'], $parsed['name'], $parsed['plural']);
    }

    public function getAreaPoly()
    {
        $area = file_get_contents((storage_path(
            sprintf('app/repositories/opg-data-%s/area.json', $this->name)
        )));

        $parsed = json_decode($area);
        $polyCoordinates = $parsed->geometry->coordinates[0];

        $result = [];
        foreach($polyCoordinates as $tuple) {
            $result[] = $tuple[0];
            $result[] = $tuple[1];
        }

        return $result;
    }
}
