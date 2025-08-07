<?php

namespace App\Http\Controllers;

use App\Facades\Fallback;
use App\Models\OsmId;
use App\Services\Language;
use App\Services\Mangrove;
use App\Services\Mapillary;
use App\Services\Overpass;
use App\Services\Repository;
use App\Services\SchemaOrg;
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

        // Fetch Mangrove reviews for all branches
        $mangroveReviews = $this->fetchMangroveReviews($branchesInfo, $place);

        // Generate Mangrove review URLs for all branches
        $mangroveReviewUrls = $this->generateMangroveReviewUrls($branchesInfo, $place);

        // Generate schema.org markup
        $schemaOrg = new SchemaOrg($this->repository);
        $schemaMarkup = $schemaOrg->generatePlaceSchema($place, $type, $main, $branchesInfo);

        return view('page.place')
            ->with('place', $place)
            ->with('logoUrl', $logoUrl)
            ->with('slug', $slug)
            ->with('main', $main)
            ->with('gallery', $place->getProcessedGallery())
            ->with('mapillaryImages', $mapillaryImages)
            ->with('mangroveReviews', $mangroveReviews)
            ->with('mangroveReviewUrls', $mangroveReviewUrls)
            ->with('branches', $branchesInfo)
            ->with('newPlaceUrl', null)
            ->with('githubUrl', $githubUrl)
            ->with('type', $type)
            ->with('color', $place->color ?? $type->color ?? 'gray')
            ->with('icon', $place->icon ?? $type->icon)
            ->with('schemaMarkup', $schemaMarkup);

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

        // Fetch Mangrove reviews for OSM place
        $mangroveReviews = $this->fetchMangroveReviews([$main], null);

        // Generate Mangrove review URLs for the main branch
        $mangroveReviewUrls = $this->generateMangroveReviewUrls([$main], null);

        // Generate schema.org markup for OSM place (create a temporary place object)
        $tempPlace = new \App\Models\Place($this->repository, '', $type->logo ?? '', $type->color ?? 'gray', [$idInfo], []);
        $schemaOrg = new SchemaOrg($this->repository);
        $schemaMarkup = $schemaOrg->generatePlaceSchema($tempPlace, $type, $main, [$main]);

        return view('page.place')
            ->with('place', null)
            ->with('logoUrl', $logoUrl)
            ->with('slug', null)
            ->with('main', $main)
            ->with('gallery', [])
            ->with('mapillaryImages', $mapillaryImages)
            ->with('mangroveReviews', $mangroveReviews)
            ->with('mangroveReviewUrls', $mangroveReviewUrls)
            ->with('branches', [$main])
            ->with('newPlaceUrl', $newPlaceUrl)
            ->with('type', $type)
            ->with('color', $type->color ?? 'gray')
            ->with('icon', $type->icon)
            ->with('schemaMarkup', $schemaMarkup);

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

        // Get subarea information if there are any subareas
        $subareas = $this->repository->getSubareas($area);

        // Get parent area for backlink
        $parentArea = $this->repository->getParentArea($areaSlug);

        return view('page.overview')
            ->with('area', $area)
            ->with('type', $type)
            ->with('logoUrl', $logoUrl)
            ->with('places', $places)
            ->with('subareas', $subareas)
            ->with('parentArea', $parentArea)
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

        // Get subarea information if there are any subareas
        $subareas = $this->repository->getSubareas($area);

        // Get parent area for backlink
        $parentArea = $this->repository->getParentArea($slug);

        // Generate schema.org markup
        $schemaOrg = new SchemaOrg($this->repository);
        $schemaMarkup = $schemaOrg->generateAreaSchema($area);

        return view('page.area')
            ->with('area', $area)
            ->with('types', $types)
            ->with('subareas', $subareas)
            ->with('parentArea', $parentArea)
            ->with('color', $area->color)
            ->with('schemaMarkup', $schemaMarkup);
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
     * @return array Array of Mapillary images with branch association
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

                    // Associate each image with the branch it came from
                    foreach ($images as &$image) {
                        $image['branch_key'] = $branch->idInfo->getKey();
                        $image['branch_name'] = \App\Facades\Fallback::field($branch->tags, 'name');
                    }

                    $allImages = array_merge($allImages, $images);
                }
            }

            // Remove duplicates based on image ID, keeping the first occurrence
            $uniqueImages = [];
            foreach ($allImages as $image) {
                if (!isset($uniqueImages[$image['id']])) {
                    $uniqueImages[$image['id']] = $image;
                }
            }

            return array_values($uniqueImages);
        } catch (\Exception $e) {
            // Log error but don't break the page if Mapillary fails
            \Illuminate\Support\Facades\Log::warning('Failed to fetch Mapillary images: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch Mangrove reviews for branches
     *
     * @param array $branches Array of OsmInfo objects
     * @param \App\Models\Place|null $place Place object for company-wide search
     * @return array Array of Mangrove reviews near the branches and company-wide
     */
    private function fetchMangroveReviews(array $branches, $place = null): array
    {
        try {
            $mangrove = new Mangrove();

            // If we have a place object, use combined search (location + company name)
            if ($place !== null) {
                // Get the main business name for company-wide search
                $companyName = '';
                if (!empty($branches)) {
                    $companyName = \App\Facades\Fallback::field($branches[0]->tags, 'name') ?? '';
                }

                $allReviews = $mangrove->getCombinedBusinessReviews($branches, $companyName);

                // Associate each review with the appropriate branch
                foreach ($allReviews as &$review) {
                    if ($review['match_type'] === 'location' && isset($review['branch_lat'], $review['branch_lon'])) {
                        // Find the closest branch for location-based reviews
                        $closestBranch = null;
                        $minDistance = PHP_FLOAT_MAX;

                        foreach ($branches as $branch) {
                            if (isset($branch->lat) && isset($branch->lon)) {
                                $distance = abs($branch->lat - $review['branch_lat']) + abs($branch->lon - $review['branch_lon']);
                                if ($distance < $minDistance) {
                                    $minDistance = $distance;
                                    $closestBranch = $branch;
                                }
                            }
                        }

                        if ($closestBranch) {
                            $review['branch_key'] = $closestBranch->idInfo->getKey();
                            $review['branch_name'] = \App\Facades\Fallback::field($closestBranch->tags, 'name');
                        }
                    } else {
                        // For company-wide reviews, don't associate with a specific branch
                        $review['branch_key'] = null;
                        $review['branch_name'] = null;
                    }
                }

                return $allReviews;
            } else {
                // Fallback to location-only search for OSM places
                $allReviews = [];

                foreach (array_slice($branches, 0, 10) as $branch) {
                    if (isset($branch->lat) && isset($branch->lon)) {
                        $reviews = $mangrove->getReviewsNearLocationMeters($branch->lat, $branch->lon, 3.0);

                        foreach ($reviews as &$review) {
                            $review['branch_key'] = $branch->idInfo->getKey();
                            $review['branch_name'] = \App\Facades\Fallback::field($branch->tags, 'name');
                            $review['match_type'] = 'location';
                        }

                        $allReviews = array_merge($allReviews, $reviews);
                    }
                }

                // Remove duplicates
                $uniqueReviews = [];
                foreach ($allReviews as $review) {
                    if (!isset($uniqueReviews[$review['id']])) {
                        $uniqueReviews[$review['id']] = $review;
                    }
                }

                return array_values($uniqueReviews);
            }
        } catch (\Exception $e) {
            // Log error but don't break the page if Mangrove fails
            \Illuminate\Support\Facades\Log::warning('Failed to fetch Mangrove reviews: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate Mangrove review URL for a location
     *
     * @param \App\Models\OsmInfo $branch
     * @return string
     */
    private function generateMangroveReviewUrl($branch): string
    {
        if (!isset($branch->lat) || !isset($branch->lon)) {
            return 'https://mangrove.reviews';
        }

        $lat = $branch->lat;
        $lon = $branch->lon;
        $name = \App\Facades\Fallback::field($branch->tags, 'name') ?? 'Location';

        // Create the geo subject string: geo:lat,lon?q=Name&u=30
        $geoSubject = sprintf('geo:%s,%s?q=%s&u=30', $lat, $lon, urlencode($name));

        // URL encode the entire subject for the search parameter
        $encodedSubject = urlencode($geoSubject);

        return sprintf('https://mangrove.reviews/search?sub=%s', $encodedSubject);
    }

    /**
     * Generate Mangrove review URLs for multiple branches
     *
     * @param array $branches
     * @param \App\Models\Place|null $place
     * @return array
     */
    private function generateMangroveReviewUrls(array $branches, $place = null): array
    {
        $urls = [];

        if (count($branches) > 1 && $place !== null) {
            // For multi-branch businesses, provide individual branch options only
            foreach ($branches as $index => $branch) {
                $branchName = \App\Facades\Fallback::field($branch->tags, 'name') ?? "Branch " . ($index + 1);
                $urls['branch_' . $branch->idInfo->getKey()] = [
                    'url' => $this->generateMangroveReviewUrl($branch),
                    'label' => $branchName,
                    'type' => 'branch',
                    'branch_key' => $branch->idInfo->getKey()
                ];
            }
        } else {
            // Single branch or OSM place
            $mainBranch = $branches[0] ?? null;
            if ($mainBranch) {
                $urls['branch_' . $mainBranch->idInfo->getKey()] = [
                    'url' => $this->generateMangroveReviewUrl($mainBranch),
                    'label' => \App\Facades\Fallback::field($mainBranch->tags, 'name') ?? 'This Location',
                    'type' => 'branch',
                    'branch_key' => $mainBranch->idInfo->getKey()
                ];
            }
        }

        return $urls;
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
