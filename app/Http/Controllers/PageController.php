<?php

namespace App\Http\Controllers;

use App\Services\Overpass;
use App\Services\Repository;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    public function bandira(Overpass $overpass)
    {
        $slug = 'bandira';
        $repositoryName = 'ethiopia';

        $tags = $this->getTags($slug, $overpass);

        $repository = new Repository($repositoryName);
        $placeInfo = $repository->getPlaceInfo($slug);
        $logoName = $placeInfo['logo'];

        $logo = "assets/$repositoryName/$slug/media/$logoName";

        return view('page.index')
            ->with('logo', $logo)
            ->with('slug', $slug)
            ->with('tags', $tags);
    }

    public function getTags(string $slug, Overpass $overpass): mixed
    {
        $tags = Cache::remember($slug, 300, function () use ($overpass) {
            return $overpass->fetchFromXapi();
        });
        return $tags;
    }
}
