<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Services\Overpass;
use App\Services\Repository;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    public function page($slug)
    {
        $repositoryName = 'ethiopia';

        $repository = new Repository($repositoryName);
        $placeInfo = $repository->getPlaceInfo($slug);
        $logoName = $placeInfo['logo'];

        $branches = $this->createPlaces($placeInfo['osmBranches'] ?? [ $placeInfo['osm'] ]);

        $branchesInfo = $this->cachedFetchOsmInfo($branches);
        $main = $branchesInfo[0];

        $logo = "assets/$repositoryName/$slug/media/$logoName";

        return view('page.page')
            ->with('logo', $logo)
            ->with('slug', $slug)
            ->with('main', $main)
            ->with('branches', $branchesInfo);
    }

    /**
     * @param array<Place> $places
     * @return array<OsmInfo>
     */
    public function cachedFetchOsmInfo(array $places): mixed
    {
        $cacheKey = implode('|', collect($places)->map(function (Place $place) {
            return $place->getKey();
        })->toArray());

        $tags = Cache::remember($cacheKey, 300, function () use ($places) {
            return (new Overpass())->fetchOsmInfo($places);
        });
        return $tags;
    }

    /**
     * @param array<array> $branches
     * @return array<Place>
     */
    private function createPlaces(mixed $branches): array
    {
        return collect($branches)->map(function (array $branch) {
            return new Place($branch['type'], $branch['id']);
        })->toArray();
    }
}
