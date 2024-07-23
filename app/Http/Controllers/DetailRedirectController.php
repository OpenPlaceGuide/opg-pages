<?php

namespace App\Http\Controllers;

use App\Facades\Fallback;
use App\Models\OsmId;
use App\Models\Place;
use App\Services\Language;
use App\Services\Overpass;
use App\Services\Repository;
use Illuminate\Routing\Controller as BaseController;

class DetailRedirectController extends BaseController
{
    public function node(string $osmId)
    {
       return $this->redirect($osmId, 'n');
    }

    public function way(string $osmId)
    {
        return $this->redirect($osmId, 'w');
    }

    public function relation(string $osmId)
    {
        return $this->redirect($osmId, 'r');
    }

    private function redirect($osmId, $osmTypeLetter)
    {
        $osmInfo = (new Overpass())->fetchOsmInfo([new OsmId($osmTypeLetter, $osmId)], Repository::getInstance()->listLeafAreas());

        $slug = Language::slug(Fallback::field($osmInfo[0]->tags, 'name', language: 'en'));
        return redirect(route('osmPlace.en', ['osmId' => $osmId, 'osmTypeLetter' => $osmTypeLetter, 'slug' => $slug]));
    }
}
