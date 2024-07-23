<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class DetailRedirectController extends BaseController
{
    public function node(string $osmId)
    {
        return redirect(route('osmPlace.en', ['osmId' => $osmId, 'osmTypeLetter' => 'n']));
    }

    public function way(string $osmId)
    {
        return redirect(route('osmPlace.en', ['osmId' => $osmId, 'osmTypeLetter' => 'w']));
    }

    public function relation(string $osmId)
    {
        return redirect(route('osmPlace.en', ['osmId' => $osmId, 'osmTypeLetter' => 'r']));
    }
}
