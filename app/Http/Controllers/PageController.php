<?php

namespace App\Http\Controllers;

use App\Facades\Fallback;
use App\Models\OsmId;
use App\Services\Language;
use App\Services\Mapillary;
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
        $type = Repository::getInstance()->resolveType($main);

        $githubUrl = sprintf('https://github.com/OpenPlaceGuide/data/tree/main/places/%s/', $slug);

        $logoUrl = $place->getLogoUrl();

        // Fetch Mapillary images for all branches
        $mapillaryImages = $this->fetchMapillaryImages($branchesInfo);

        return view('page.place')
            ->with('place', $place)
            ->with('logoUrl', $logoUrl)
            ->with('slug', $slug)
            ->with('main', $main)
            ->with('gallery', $place->getProcessedGallery())
            ->with('mapillaryImages', $mapillaryImages)
            ->with('branches', $branchesInfo)
            ->with('newPlaceUrl', null)
            ->with('githubUrl', $githubUrl)
            ->with('type', $type)
            ->with('color', $place->color ?? $type->color ?? 'gray')
            ->with('icon', $place->icon ?? $type->icon);

    }

    public function osmPlace($type, $id)
    {
        // FIXME: forward to slug based page if existing
        $idInfo = new OsmId($type, $id);

        if ($place = Repository::getInstance()->resolvePlace($idInfo)) {
            return redirect()->to($place->getUrl($idInfo));
        }

        $main = $this->fetchOsmInfo([$idInfo])[0];

        $newPlaceContent = <<<YAML
osm:
   id: {$idInfo->osmId}
   type: {$idInfo->osmType}
YAML;

        $name = Language::slug(Fallback::field($main->tags, 'name', language: 'en'));
        // FIXME: don't hard code the data repository
        $newPlaceUrl = sprintf('https://github.com/OpenPlaceGuide/data/new/main?filename=places/%s/place.yaml&value=%s', $name, urlencode($newPlaceContent));

        $type = Repository::getInstance()->resolveType($main);

        $logoUrl = $type->getLogoUrl();

        // Fetch Mapillary images for OSM place
        $mapillaryImages = $this->fetchMapillaryImages([$main]);

        return view('page.place')
            ->with('place', null)
            ->with('logoUrl', $logoUrl)
            ->with('slug', null)
            ->with('main', $main)
            ->with('gallery', [])
            ->with('mapillaryImages', $mapillaryImages)
            ->with('branches', [$main])
            ->with('newPlaceUrl', $newPlaceUrl)
            ->with('type', $type)
            ->with('color', $type->color ?? 'gray')
            ->with('icon', $type->icon);

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

        $logoUrl = $type->getLogoUrl();


        return view('page.overview')
            ->with('area', $area)
            ->with('type', $type)
            ->with('logoUrl', $logoUrl)
            ->with('places', $places)
            ->with('color', $type->color)
            ->with('logo', $type->logo);
    }

    /**
     * POI overview page
     */
    public function area(string $slug)
    {
        $types = $this->repository->listTypes();

        $area = $this->repository->getAreaInfo($slug);

        return view('page.area')
            ->with('area', $area)
            ->with('types', $types)
            ->with('color', $area->color);
    }

    /**
     * @param array<OsmId> $places
     * @return array<OsmInfo>
     */
    private function fetchOsmInfo(array $places): array
    {
        return (new Overpass())->fetchOsmInfo($places, Repository::getInstance()->listLeafAreas());
    }

    /**
     * Fetch Mapillary images for branches
     *
     * @param array $branches Array of OsmInfo objects
     * @return array Array of Mapillary images
     */
    private function fetchMapillaryImages(array $branches): array
    {
        try {
            $mapillary = new Mapillary();
            $allImages = [];

            if (count($branches) > 1) {
                $limit = 1;
            } else {
                $limit = 3;
            }
            foreach (array_slice($branches, 0, 10) as $branch) {
                if (isset($branch->lat) && isset($branch->lon)) {
                    $images = $mapillary->getImagesNearLocation($branch->lat, $branch->lon, 50, $limit);
                    $allImages = array_merge($allImages, $images);
                }
            }

            // Remove duplicates based on image ID
            $uniqueImages = [];
            foreach ($allImages as $image) {
                $uniqueImages[$image['id']] = $image;
            }

            return array_values($uniqueImages);
        } catch (\Exception $e) {
            // Log error but don't break the page if Mapillary fails
            \Illuminate\Support\Facades\Log::warning('Failed to fetch Mapillary images: ' . $e->getMessage());
            return [];
        }
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
