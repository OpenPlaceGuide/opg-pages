<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Fallback extends Facade
{
    protected static function getFacadeAccessor() { return 'fallback'; }
}
