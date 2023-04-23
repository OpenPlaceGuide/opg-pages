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



    public function page(string $slug)
    {
        if ($this->repository->isArea($slug)) {
            return $this->area($slug);
        }

        return $this->place($slug);
    }

    public function osmPlace($type, $id)
    {
        return 'Not implemented yet';
    }

    public function typePage(string $areaSlug, string $typeSlug)
    {
        if (!$this->repository->isType($typeSlug)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid place type', $typeSlug));
        }

        if (!$this->repository->isArea($areaSlug)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid place type', $typeSlug));
        }

        $type = $this->repository->getTypeInfo($typeSlug);
        $area = $this->repository->getAreaInfo($areaSlug);

        $places = (new Overpass())->fetchOsmOverview($type, $area);

        return view('page.overview')
            ->with('area', $area)
            ->with('type', $type)
            ->with('places', $places);
    }

    public function place(string $slug)
    {
        $place = $this->repository->getPlaceInfo($slug);

        $branchesInfo = $this->fetchOsmInfo($place->branches);
        $main = $branchesInfo[0];

        $logoUrl = $place->getLogoUrl();

        $newPlaceContent = <<<YAML
osm:
   id: {$place->branches[0]->osmId}
   type: {$place->branches[0]->osmType}
YAML;

        return view('page.page')
            ->with('place', $place)
            ->with('logoUrl', $logoUrl)
            ->with('slug', $slug)
            ->with('main', $main)
            ->with('gallery', $place->getProcessedGallery('en'))
            ->with('branches', $branchesInfo)
            ->with('newPlaceContent', $newPlaceContent);
    }
    /**
     * POI overview page
     */
    public function area(string $slug)
    {
        $types = $this->repository->listTypes($slug);
        $area = $this->repository->getAreaInfo($slug);

        return view('page.area')
            ->with('area', $area)
            ->with('types', $types);
    }

    /**
     * @param array<Branch> $places
     * @return array<OsmInfo>
     */
    private function fetchOsmInfo(array $places): array
    {
        return (new Overpass())->fetchOsmInfo($places);
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
