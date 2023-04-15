<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Place;
use Symfony\Component\Yaml\Yaml;

class Repository
{
    public function __construct(public readonly string $name)
    {
    }

    public function getPlaceInfo($slug): Place
    {
        $yamlSource = file_get_contents(storage_path(
            sprintf('app/repositories/opg-data-%s/places/%s/place.yaml', $this->name, $slug)
        ));

        $parsed = Yaml::parse($yamlSource);

        $branches = $this->createPlaces($parsed['osmBranches'] ?? [$parsed['osm']]);

        $gallery = $parsed['gallery'] ?? [];

        return new Place($this, $slug, $parsed['logo'], $branches, $gallery);
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

}
