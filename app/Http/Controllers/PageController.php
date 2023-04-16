<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\Overpass;
use App\Services\Repository;
use Bame\StaticMap\TripleZoomMap;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    private Repository $repository;

    public function __construct()
    {
        $repositoryName = 'ethiopia';
        $this->repository = new Repository($repositoryName);
    }


    /**
     * Place page
     */
    public function page(string $slug)
    {
        if ($this->repository->isType($slug)) {
            return $this->overview($slug);
        }

        $place = $this->repository->getPlaceInfo($slug);

        $branchesInfo = $this->cachedFetchOsmInfo($place->branches);
        $main = $branchesInfo[0];

        $logoUrl = $place->getLogoUrl();

        return view('page.page')
            ->with('place', $place)
            ->with('logoUrl', $logoUrl)
            ->with('slug', $slug)
            ->with('main', $main)
            ->with('gallery', $place->getProcessedGallery('en'))
            ->with('branches', $branchesInfo);
    }

    /**
     * POI overview page
     */
    public function overview(string $slug)
    {
        $type = $this->repository->getTypeInfo($slug);
        $places = (new Overpass())->fetchOsmOverview($type);

        return view('page.overview')
            ->with('type', $type)
            ->with('places', $places);
    }

    /**
     * @param array<Branch> $places
     * @return array<OsmInfo>
     */
    public function cachedFetchOsmInfo(array $places): mixed
    {
        $cacheKey = implode('|', collect($places)->map(function (Branch $place) {
            return $place->getKey();
        })->toArray());

        $tags = Cache::remember($cacheKey, 300, function () use ($places) {
            return (new Overpass())->fetchOsmInfo($places);
        });
        return $tags;
    }

    public function tripleZoomMap($lat, $lon, $text)
    {
        $colors = [
            [0x00, 0x6B, 0x3F],
            [0xF9, 0xDD, 0x16],
            [0xE2, 0x3D, 0x28],
        ];

        $map = new TripleZoomMap(
            $lat,
            $lon,
            700,
            320,
            $colors,
            'https://a.africa.tiles.openplaceguide.org/styles/bright/{Z}/{X}/{Y}.png',
            'opg-pages');
        $map->addSignPost(
            $text,
            resource_path('images/signpost.png'),
            resource_path('fonts/NotoSansWithEthiopic.ttf'),
        );
        $map->sendHeader();


        return imagepng($map->getImage());

    }
}
