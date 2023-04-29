<?php

namespace App\Http\Controllers;

use App\Facades\Fallback;
use App\Models\Branch;
use App\Services\Language;
use App\Services\Overpass;
use App\Services\Repository;
use Bame\StaticMap\TripleZoomMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

class PageController extends Controller
{
    private Repository $repository;

    public function __construct()
    {
        $repositoryName = 'ethiopia';
        $this->repository = new Repository($repositoryName);

        if (Route::current()) {
            $language = trim(Route::current()->getPrefix(), '/');
            if (!empty($language)) {
                App::setLocale($language);
            }
        }
    }

    public function page(string $slug)
    {
        if ($this->repository->isArea($slug)) {
            return $this->area($slug);
        }

        return $this->place($slug);
    }


    public function place(string $slug)
    {
        $place = $this->repository->getPlaceInfo($slug);

        $branchesInfo = $this->fetchOsmInfo($place->branches);
        $main = $branchesInfo[0];

        $logoUrl = $place->getLogoUrl();

        return view('page.page')
            ->with('place', $place)
            ->with('logoUrl', $logoUrl)
            ->with('slug', $slug)
            ->with('main', $main)
            ->with('gallery', $place->getProcessedGallery('en'))
            ->with('branches', $branchesInfo)
            ->with('newPlaceUrl', null)
            ->with('color', $place->color);
    }

    public function osmPlace($type, $id)
    {
        $branch = new Branch($type, $id);
        $main = $this->fetchOsmInfo([$branch])[0];

        $newPlaceContent = <<<YAML
osm:
   id: {$branch->osmId}
   type: {$branch->osmType}
YAML;

        $name = Language::slug(Fallback::field($main->tags, 'name', language: 'en'));
        $newPlaceUrl = sprintf('https://github.com/OpenPlaceGuide/data/new/main?filename=places/%s/place.yaml&value=%s', $name, urlencode($newPlaceContent));

        $color = 'gray'; // FIXME: get color from POI Type
        return view('page.page')
            ->with('place', null)
            ->with('logoUrl', null)
            ->with('slug', null)
            ->with('main', $main)
            ->with('gallery', [])
            ->with('branches', [$main])
            ->with('newPlaceUrl', $newPlaceUrl)
            ->with('color', $color);

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
            ->with('places', $places)
            ->with('color', $type->color);
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
            ->with('types', $types)
            ->with('color', $area->color);

    }

    /**
     * @param array<Branch> $places
     * @return array<OsmInfo>
     */
    private function fetchOsmInfo(array $places): array
    {
        return (new Overpass())->fetchOsmInfo($places);
    }

    public function tripleZoomMap($lat, $lon, Request $request)
    {
        $text = $request->query('text');

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

//        $map->sendHeader();

        $response = Response::stream(function() use($map) {
            imagepng($map->getImage());
        }, 200, ["Content-Type"=> 'image/png']);

        return $response;

    }
}
